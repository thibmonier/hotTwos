<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Valuation;

use App\Application\Pricing\Message\ProfileRateDefined;
use App\Application\Timesheet\Message\TimeEntriesValidated;
use App\Application\Valuation\RevalueOnRateDefinedHandler;
use App\Application\Valuation\ValueValidatedTimeHandler;
use App\Domain\Pricing\ProfileAssignment;
use App\Domain\Pricing\ProfileRate;
use App\Domain\Pricing\RateResolver;
use App\Domain\Shared\EffectivePeriod;
use App\Domain\Tenant\TenantId;
use App\Domain\Timesheet\TimeEntry;
use App\Domain\Valuation\TimeValuationCalculator;
use App\Domain\Valuation\ValuationStatus;
use App\Tests\Support\Analytics\InMemoryEventStore;
use App\Tests\Support\Messaging\RecordingMessageBus;
use App\Tests\Support\Pricing\InMemoryProfileAssignmentRepository;
use App\Tests\Support\Pricing\InMemoryProfileRateRepository;
use App\Tests\Support\Timesheet\InMemoryTimeEntryRepository;
use App\Tests\Support\Valuation\InMemoryTimeEntryValuationRepository;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

/**
 * US-060 (T-060-07, CA-2 / INV-2 / INV-3) — la valorisation est **figée** à la validation :
 * une révision tarifaire ultérieure ne réécrit jamais une valorisation passée et ne déclenche
 * aucun recalcul rétroactif des imputations déjà valorisées.
 */
final class FrozenSnapshotTest extends TestCase
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
    private ValueValidatedTimeHandler $value;
    private RevalueOnRateDefinedHandler $revalue;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $this->entries = new InMemoryTimeEntryRepository();
        $this->assignments = new InMemoryProfileAssignmentRepository();
        $this->rates = new InMemoryProfileRateRepository();
        $this->valuations = new InMemoryTimeEntryValuationRepository();
        $this->events = new InMemoryEventStore();
        $this->bus = new RecordingMessageBus();

        // Bus dédié au handler de valorisation : ses demandes de rebuild analytique (T-060-06) ne
        // doivent pas polluer $this->bus, qui observe le re-déclenchement CA-4.
        $this->value = new ValueValidatedTimeHandler(
            $this->entries,
            $this->assignments,
            new RateResolver($this->rates),
            new TimeValuationCalculator(),
            $this->valuations,
            $this->events,
            new RecordingMessageBus(),
        );
        $this->revalue = new RevalueOnRateDefinedHandler(
            $this->valuations,
            $this->bus,
            new MockClock(new DateTimeImmutable('2026-09-01 10:00:00', new DateTimeZone('UTC'))),
        );
    }

    public function testLaterRateRevisionDoesNotRewriteAPastValuation(): void
    {
        $this->assignments->save(new ProfileAssignment($this->tenant, self::USER, self::PROFILE, EffectivePeriod::since($this->date('2026-01-01'))));
        // Taux en vigueur à la validation : 45000 / 78000.
        $this->rates->save(new ProfileRate($this->tenant, self::PROFILE, EffectivePeriod::between($this->date('2026-01-01'), $this->date('2026-09-01')), 45000, 78000));
        $entryId = $this->saveValidatedEntry($this->date('2026-08-15'), 420);

        // Valorisation initiale, figée au taux de 78000.
        ($this->value)(new TimeEntriesValidated($this->tenant->toString(), [$entryId], $this->at('2026-08-20 10:00:00')));
        $frozen = $this->valuations->findForTimeEntry($this->tenant, $entryId);
        self::assertNotNull($frozen);
        self::assertSame(78000, $frozen->revenueCents());
        self::assertSame(78000, $frozen->snapshotSellingRateCents());

        // Révision tarifaire ultérieure (période disjointe) + re-déclenchement automatique (CA-4).
        $this->rates->save(new ProfileRate($this->tenant, self::PROFILE, EffectivePeriod::since($this->date('2026-09-01')), 50000, 90000));
        ($this->revalue)(new ProfileRateDefined($this->tenant->toString(), self::PROFILE));

        // Aucun recalcul rétroactif : l'imputation déjà valorisée n'est pas re-déclenchée…
        self::assertSame([], $this->bus->dispatched, 'Une imputation déjà valorisée n\'est jamais re-valorisée.');
        // …et son snapshot reste inchangé (INV-2/INV-3).
        $after = $this->valuations->findForTimeEntry($this->tenant, $entryId);
        self::assertNotNull($after);
        self::assertSame(78000, $after->revenueCents());
        self::assertSame(78000, $after->snapshotSellingRateCents());
        self::assertSame(45000, $after->snapshotCostRateCents());
        // Un seul CA reconnu, jamais dédoublé.
        self::assertCount(1, $this->events->appended);
    }

    public function testMissingRateEntryIsValuedOnceRateIsAdded(): void
    {
        // Aucun profil au départ → valorisation partielle (missing_rate).
        $entryId = $this->saveValidatedEntry($this->date('2026-08-15'), 420);
        ($this->value)(new TimeEntriesValidated($this->tenant->toString(), [$entryId], $this->at('2026-08-20 10:00:00')));
        self::assertSame(ValuationStatus::MISSING_RATE, $this->valuations->findForTimeEntry($this->tenant, $entryId)?->status());
        self::assertSame([], $this->events->appended, 'Une imputation sans tarif ne reconnaît aucun CA.');

        // Le tarif est renseigné : le re-déclenchement ré-émet la validation…
        $this->assignments->save(new ProfileAssignment($this->tenant, self::USER, self::PROFILE, EffectivePeriod::since($this->date('2026-01-01'))));
        $this->rates->save(new ProfileRate($this->tenant, self::PROFILE, EffectivePeriod::since($this->date('2026-01-01')), 45000, 78000));
        ($this->revalue)(new ProfileRateDefined($this->tenant->toString(), self::PROFILE));

        self::assertCount(1, $this->bus->dispatched);
        $message = $this->bus->dispatched[0];
        self::assertInstanceOf(TimeEntriesValidated::class, $message);

        // …que le handler de valorisation consomme : l'imputation devient valued.
        ($this->value)($message);
        $valued = $this->valuations->findForTimeEntry($this->tenant, $entryId);
        self::assertNotNull($valued);
        self::assertSame(ValuationStatus::VALUED, $valued->status());
        self::assertSame(78000, $valued->revenueCents());
        self::assertCount(1, $this->events->appended, 'Le CA n\'est reconnu qu\'une fois l\'imputation valorisée.');
    }

    private function saveValidatedEntry(DateTimeImmutable $workDate, int $minutes): string
    {
        $entry = new TimeEntry($this->tenant, self::USER, self::PROJECT, $workDate, $minutes);
        $entry->validate(self::USER, $this->at('2026-08-20 09:00:00'));
        $this->entries->save($entry);

        return $entry->id();
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
