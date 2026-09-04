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
 * US-060 (T-060-07, ENF-PERF-5) — smoke de débit : la valorisation de 1 000 imputations doit
 * tenir très en deçà du plafond de 15 minutes. Ce test unitaire (en mémoire, sans I/O) borne le
 * coût algorithmique par imputation ; la validation de charge réelle (throughput ≥ 67/s avec
 * transport Doctrine) est menée sur l'environnement de staging (cf. notes de la story).
 */
final class ValuationThroughputTest extends TestCase
{
    private const int COUNT = 1000;
    private const string USER = '018f9c4e-0000-7000-8000-0000000000aa';
    private const string PROFILE = '018f9c4e-0000-7000-8000-0000000000cc';
    private const string PROJECT = '018f9c4e-0000-7000-8000-0000000000bb';

    public function testValuesOneThousandEntriesWellUnderTheLimit(): void
    {
        $tenant = TenantId::generate();
        $entries = new InMemoryTimeEntryRepository();
        $assignments = new InMemoryProfileAssignmentRepository();
        $rates = new InMemoryProfileRateRepository();
        $valuations = new InMemoryTimeEntryValuationRepository();
        $events = new InMemoryEventStore();

        $assignments->save(new ProfileAssignment($tenant, self::USER, self::PROFILE, EffectivePeriod::since($this->date('2026-01-01'))));
        $rates->save(new ProfileRate($tenant, self::PROFILE, EffectivePeriod::since($this->date('2026-01-01')), 45000, 78000));

        $ids = [];
        for ($i = 0; $i < self::COUNT; ++$i) {
            $entry = new TimeEntry($tenant, self::USER, self::PROJECT.'-'.$i, $this->date('2026-08-15'), 420);
            $entries->save($entry);
            $ids[] = $entry->id();
        }

        $handler = new ValueValidatedTimeHandler(
            $entries,
            $assignments,
            new RateResolver($rates),
            new TimeValuationCalculator(),
            $valuations,
            $events,
            new RecordingMessageBus(),
        );

        $start = microtime(true);
        $handler(new TimeEntriesValidated($tenant->toString(), $ids, $this->at('2026-08-20 10:00:00')));
        $elapsed = microtime(true) - $start;

        self::assertCount(self::COUNT, $events->appended, 'Chaque imputation valorisée reconnaît son CA.');
        self::assertSame(ValuationStatus::VALUED, $valuations->findForTimeEntry($tenant, $ids[0])?->status());
        self::assertLessThan(30.0, $elapsed, sprintf('1 000 imputations valorisées en %.2fs — plafond métier : 900s (15 min).', $elapsed));
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
