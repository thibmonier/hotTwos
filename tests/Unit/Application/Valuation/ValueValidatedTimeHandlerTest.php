<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Valuation;

use App\Application\Timesheet\Message\TimeEntriesValidated;
use App\Application\Valuation\ValueValidatedTimeHandler;
use App\Domain\Pricing\ProfileAssignment;
use App\Domain\Pricing\ProfileRate;
use App\Domain\Pricing\RateResolver;
use App\Domain\Shared\EffectivePeriod;
use App\Domain\Tenant\TenantId;
use App\Domain\Timesheet\TimeEntry;
use App\Domain\Valuation\TimeValuationCalculator;
use App\Domain\Valuation\ValuationStatus;
use App\Application\Analytics\Message\AnalyticsRebuildRequested;
use App\Tests\Support\Analytics\InMemoryEventStore;
use App\Tests\Support\Messaging\RecordingMessageBus;
use App\Tests\Support\Pricing\InMemoryProfileAssignmentRepository;
use App\Tests\Support\Pricing\InMemoryProfileRateRepository;
use App\Tests\Support\Timesheet\InMemoryTimeEntryRepository;
use App\Tests\Support\Valuation\InMemoryTimeEntryValuationRepository;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

/**
 * US-060 (T-060-02) — à la validation, chaque imputation est valorisée avec le tarif en vigueur
 * à sa date, le snapshot figé ; sans profil ou sans tarif, la valorisation est `missing_rate`.
 */
final class ValueValidatedTimeHandlerTest extends TestCase
{
    private const string USER = '018f9c4e-0000-7000-8000-0000000000aa';
    private const string PROFILE = '018f9c4e-0000-7000-8000-0000000000cc';
    private const string PROJECT = '018f9c4e-0000-7000-8000-0000000000bb';

    private TenantId $tenant;
    private InMemoryTimeEntryRepository $entries;
    private InMemoryProfileAssignmentRepository $assignments;
    private InMemoryProfileRateRepository $rates;
    private InMemoryTimeEntryValuationRepository $valuations;
    private InMemoryEventStore $events;
    private RecordingMessageBus $bus;
    private ValueValidatedTimeHandler $handler;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $this->entries = new InMemoryTimeEntryRepository();
        $this->assignments = new InMemoryProfileAssignmentRepository();
        $this->rates = new InMemoryProfileRateRepository();
        $this->valuations = new InMemoryTimeEntryValuationRepository();
        $this->events = new InMemoryEventStore();
        $this->bus = new RecordingMessageBus();
        $this->handler = new ValueValidatedTimeHandler(
            $this->entries,
            $this->assignments,
            new RateResolver($this->rates),
            new TimeValuationCalculator(),
            $this->valuations,
            $this->events,
            $this->bus,
        );
    }

    public function testValuesEntryWithFrozenSnapshot(): void
    {
        $this->assignProfile();
        $this->rates->save(new ProfileRate($this->tenant, self::PROFILE, EffectivePeriod::since($this->date('2026-01-01')), 45000, 78000));
        $entryId = $this->saveEntry($this->date('2026-06-15'), 420);

        $this->handle([$entryId]);

        $valuation = $this->valuations->findForTimeEntry($this->tenant, $entryId);
        self::assertNotNull($valuation);
        self::assertSame(ValuationStatus::VALUED, $valuation->status());
        self::assertSame(45000, $valuation->costCents());
        self::assertSame(78000, $valuation->revenueCents());
        self::assertSame(45000, $valuation->snapshotCostRateCents());
        self::assertSame(78000, $valuation->snapshotSellingRateCents());
    }

    public function testHalfDayIsValuedProRata(): void
    {
        $this->assignProfile();
        $this->rates->save(new ProfileRate($this->tenant, self::PROFILE, EffectivePeriod::since($this->date('2026-01-01')), 45000, 78000));
        $entryId = $this->saveEntry($this->date('2026-06-15'), 210);

        $this->handle([$entryId]);

        self::assertSame(22500, $this->valuations->findForTimeEntry($this->tenant, $entryId)?->costCents());
    }

    public function testMissingProfileYieldsMissingRate(): void
    {
        $this->rates->save(new ProfileRate($this->tenant, self::PROFILE, EffectivePeriod::since($this->date('2026-01-01')), 45000, 78000));
        $entryId = $this->saveEntry($this->date('2026-06-15'), 420);

        $this->handle([$entryId]);

        self::assertSame(ValuationStatus::MISSING_RATE, $this->valuations->findForTimeEntry($this->tenant, $entryId)?->status());
    }

    public function testMissingRateYieldsMissingRate(): void
    {
        $this->assignProfile();
        // Aucun tarif défini pour le profil.
        $entryId = $this->saveEntry($this->date('2026-06-15'), 420);

        $this->handle([$entryId]);

        self::assertSame(ValuationStatus::MISSING_RATE, $this->valuations->findForTimeEntry($this->tenant, $entryId)?->status());
    }

    public function testValuedEntryRecognizesRealRevenueEvent(): void
    {
        $this->assignProfile();
        $this->rates->save(new ProfileRate($this->tenant, self::PROFILE, EffectivePeriod::since($this->date('2026-01-01')), 45000, 78000));
        $entryId = $this->saveEntry($this->date('2026-06-15'), 420);

        $this->handle([$entryId]);

        self::assertCount(1, $this->events->appended, 'Une imputation valorisée reconnaît un CA réel (T-060-04).');
        $event = $this->events->appended[0];
        self::assertSame('revenue_recognized', $event->name());
        self::assertSame([
            'period' => '2026-06',
            'project_ref' => self::PROJECT,
            'amount_cents' => 78000,
            'source_time_entry_id' => $entryId,
        ], $event->payload(), 'CA reconnu sur le mois de prestation, montant = revenu figé, source tracée.');
    }

    public function testMissingRateRecognizesNoRevenue(): void
    {
        $this->assignProfile();
        // Aucun tarif : valorisation partielle (missing_rate) → aucun CA reconnu (CA-4).
        $entryId = $this->saveEntry($this->date('2026-06-15'), 420);

        $this->handle([$entryId]);

        self::assertSame([], $this->events->appended);
    }

    public function testValuedEntryRequestsAnalyticsRebuild(): void
    {
        $this->assignProfile();
        $this->rates->save(new ProfileRate($this->tenant, self::PROFILE, EffectivePeriod::since($this->date('2026-01-01')), 45000, 78000));
        $entryId = $this->saveEntry($this->date('2026-06-15'), 420);

        $this->handle([$entryId]);

        // Le CA reconnu → une demande de rematérialisation de fact_project_revenue (T-060-06).
        self::assertCount(1, $this->bus->dispatched);
        self::assertInstanceOf(AnalyticsRebuildRequested::class, $this->bus->dispatched[0]);
        self::assertTrue($this->bus->dispatched[0]->tenantId()->equals($this->tenant));
    }

    public function testMissingRateRequestsNoRebuild(): void
    {
        $this->assignProfile();
        // Rien de valorisé (aucun tarif) → aucune projection à rejouer.
        $entryId = $this->saveEntry($this->date('2026-06-15'), 420);

        $this->handle([$entryId]);

        self::assertSame([], $this->bus->dispatched);
    }

    private function assignProfile(): void
    {
        $this->assignments->save(new ProfileAssignment($this->tenant, self::USER, self::PROFILE, EffectivePeriod::since($this->date('2026-01-01'))));
    }

    private function saveEntry(DateTimeImmutable $workDate, int $minutes): string
    {
        $entry = new TimeEntry($this->tenant, self::USER, self::PROJECT, $workDate, $minutes);
        $this->entries->save($entry);

        return $entry->id();
    }

    /**
     * @param list<string> $entryIds
     */
    private function handle(array $entryIds): void
    {
        ($this->handler)(new TimeEntriesValidated($this->tenant->toString(), $entryIds, $this->at('2026-06-20 10:00:00')));
    }

    private function date(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value.' 00:00:00', new DateTimeZone('UTC'));
    }

    private function at(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value, new DateTimeZone('UTC'));
    }
}
