<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Period;

use App\Application\Period\Message\PeriodClosed;
use App\Application\Period\TriggerDownstreamOnPeriodClosed;
use App\Application\Timesheet\Message\TimeEntriesValidated;
use App\Domain\Tenant\TenantId;
use App\Domain\Timesheet\TimeEntry;
use App\Tests\Support\Messaging\RecordingMessageBus;
use App\Tests\Support\Timesheet\InMemoryTimeEntryRepository;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

/**
 * US-057 (T-057-06, CA-1) — à la clôture, les imputations validées du mois sont ré-émises pour
 * (re)déclencher leur valorisation.
 */
final class TriggerDownstreamOnPeriodClosedTest extends TestCase
{
    private const string USER = '018f9c4e-0000-7000-8000-0000000000aa';
    private const string PROJECT = '018f9c4e-0000-7000-8000-0000000000bb';

    public function testReDispatchesValidatedEntriesOfTheClosedMonth(): void
    {
        $tenant = TenantId::generate();
        $entries = new InMemoryTimeEntryRepository();
        $bus = new RecordingMessageBus();

        $inAugust = $this->validatedEntry($tenant, $this->date('2026-08-10'));
        $entries->save($inAugust);
        $entries->save($this->validatedEntry($tenant, $this->date('2026-07-31'))); // hors mois
        $entries->save(new TimeEntry($tenant, self::USER, self::PROJECT, $this->date('2026-08-20'), 210)); // non validée

        $handler = new TriggerDownstreamOnPeriodClosed(
            $entries,
            $bus,
            new MockClock(new DateTimeImmutable('2026-09-01 10:00:00', new DateTimeZone('UTC'))),
        );

        ($handler)(new PeriodClosed($tenant->toString(), '2026-08'));

        self::assertCount(1, $bus->dispatched);
        $message = $bus->dispatched[0];
        self::assertInstanceOf(TimeEntriesValidated::class, $message);
        self::assertSame([$inAugust->id()], $message->timeEntryIds());
    }

    public function testDoesNothingWithoutValidatedEntries(): void
    {
        $tenant = TenantId::generate();
        $bus = new RecordingMessageBus();
        $handler = new TriggerDownstreamOnPeriodClosed(new InMemoryTimeEntryRepository(), $bus, new MockClock());

        ($handler)(new PeriodClosed($tenant->toString(), '2026-08'));

        self::assertSame([], $bus->dispatched);
    }

    private function validatedEntry(TenantId $tenant, DateTimeImmutable $workDate): TimeEntry
    {
        $entry = new TimeEntry($tenant, self::USER, self::PROJECT, $workDate, 420);
        $entry->validate(self::USER, new DateTimeImmutable('2026-08-31 18:00:00', new DateTimeZone('UTC')));

        return $entry;
    }

    private function date(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value.' 00:00:00', new DateTimeZone('UTC'));
    }
}
