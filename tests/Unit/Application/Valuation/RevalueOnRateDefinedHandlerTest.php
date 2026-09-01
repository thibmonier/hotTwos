<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Valuation;

use App\Application\Pricing\Message\ProfileRateDefined;
use App\Application\Timesheet\Message\TimeEntriesValidated;
use App\Application\Valuation\RevalueOnRateDefinedHandler;
use App\Domain\Tenant\TenantId;
use App\Domain\Valuation\TimeEntryValuation;
use App\Tests\Support\Messaging\RecordingMessageBus;
use App\Tests\Support\Valuation\InMemoryTimeEntryValuationRepository;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

/**
 * US-060 (T-060-05, CA-4) — à la définition d'un tarif, la valorisation des imputations restées
 * sans tarif (`missing_rate`) est re-déclenchée automatiquement.
 */
final class RevalueOnRateDefinedHandlerTest extends TestCase
{
    private TenantId $tenant;
    private InMemoryTimeEntryValuationRepository $valuations;
    private RecordingMessageBus $bus;
    private RevalueOnRateDefinedHandler $handler;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $this->valuations = new InMemoryTimeEntryValuationRepository();
        $this->bus = new RecordingMessageBus();
        $this->handler = new RevalueOnRateDefinedHandler(
            $this->valuations,
            $this->bus,
            new MockClock(new DateTimeImmutable('2026-09-01 10:00:00', new DateTimeZone('UTC'))),
        );
    }

    public function testReTriggersValuationForMissingRateEntries(): void
    {
        $valuedAt = new DateTimeImmutable('2026-08-20 09:00:00', new DateTimeZone('UTC'));
        $this->valuations->save(TimeEntryValuation::missingRate($this->tenant, 'entry-1', $valuedAt));
        $this->valuations->save(TimeEntryValuation::missingRate($this->tenant, 'entry-2', $valuedAt));

        ($this->handler)(new ProfileRateDefined($this->tenant->toString(), 'profile-1'));

        self::assertCount(1, $this->bus->dispatched);
        $message = $this->bus->dispatched[0];
        self::assertInstanceOf(TimeEntriesValidated::class, $message);
        self::assertSame(['entry-1', 'entry-2'], $message->timeEntryIds());
        self::assertTrue($message->tenantId()->equals($this->tenant));
    }

    public function testDoesNothingWhenNoMissingRate(): void
    {
        ($this->handler)(new ProfileRateDefined($this->tenant->toString(), 'profile-1'));

        self::assertSame([], $this->bus->dispatched);
    }

    public function testIsScopedToTenant(): void
    {
        $other = TenantId::generate();
        $this->valuations->save(TimeEntryValuation::missingRate($other, 'entry-x', new DateTimeImmutable('2026-08-20 09:00:00', new DateTimeZone('UTC'))));

        ($this->handler)(new ProfileRateDefined($this->tenant->toString(), 'profile-1'));

        self::assertSame([], $this->bus->dispatched, 'Le re-déclenchement ne touche pas les imputations d\'un autre tenant.');
    }
}
