<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Analytics;

use App\Application\Analytics\AnalyticsRebuildScheduler;
use App\Application\Analytics\Message\AnalyticsRebuildRequested;
use App\Domain\Tenant\TenantId;
use App\Tests\Support\Messaging\RecordingMessageBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * US-060 (T-060-09) — coalescence : tant qu'un rebuild est en attente pour un tenant, les demandes
 * suivantes n'émettent pas de nouveau message ; l'acquittement ré-ouvre la coalescence.
 */
final class AnalyticsRebuildSchedulerTest extends TestCase
{
    private RecordingMessageBus $bus;
    private AnalyticsRebuildScheduler $scheduler;

    protected function setUp(): void
    {
        $this->bus = new RecordingMessageBus();
        $this->scheduler = new AnalyticsRebuildScheduler($this->bus, new ArrayAdapter());
    }

    public function testConsecutiveRequestsAreCoalesced(): void
    {
        $tenant = TenantId::generate();

        $this->scheduler->schedule($tenant);
        $this->scheduler->schedule($tenant);
        $this->scheduler->schedule($tenant);

        // Un seul message malgré 3 demandes rapprochées.
        self::assertCount(1, $this->bus->dispatched);
        self::assertInstanceOf(AnalyticsRebuildRequested::class, $this->bus->dispatched[0]);
    }

    public function testAcknowledgeReopensCoalescing(): void
    {
        $tenant = TenantId::generate();

        $this->scheduler->schedule($tenant);   // dispatch #1
        $this->scheduler->acknowledge($tenant); // le worker commence le rebuild
        $this->scheduler->schedule($tenant);   // dispatch #2 (nouvelle rafale)

        self::assertCount(2, $this->bus->dispatched);
    }

    public function testFlagIsPerTenant(): void
    {
        $a = TenantId::generate();
        $b = TenantId::generate();

        $this->scheduler->schedule($a);
        $this->scheduler->schedule($b);

        // Chaque tenant a son propre drapeau : deux messages.
        self::assertCount(2, $this->bus->dispatched);
    }
}
