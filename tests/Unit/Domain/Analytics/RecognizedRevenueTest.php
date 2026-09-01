<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Analytics;

use App\Domain\Analytics\RecognizedRevenue;
use App\Domain\Analytics\RevenueRecognized;
use App\Domain\Analytics\StoredEvent;
use App\Domain\Tenant\TenantId;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * US-060 (T-060-04) — sémantique de projection du CA reconnu, partagée par le projecteur et
 * le vérificateur de non-divergence.
 */
final class RecognizedRevenueTest extends TestCase
{
    private ?TenantId $tenant = null;

    public function testSumsStandaloneProbeRecognitionsByPeriodAndProject(): void
    {
        $stream = $this->stream(
            new RevenueRecognized($this->tenant(), '2026-08', 'PRJ-1', 120000, $this->at()),
            new RevenueRecognized($this->tenant(), '2026-08', 'PRJ-1', 30000, $this->at()),
            new RevenueRecognized($this->tenant(), '2026-08', 'PRJ-2', 50000, $this->at()),
        );

        self::assertSame([
            '2026-08' => ['PRJ-1' => 150000, 'PRJ-2' => 50000],
        ], RecognizedRevenue::byPeriodAndProject($stream));
    }

    public function testLatestRecognitionWinsPerSourceTimeEntry(): void
    {
        // Deux reconnaissances pour la même imputation (re-valorisation) : la dernière remplace
        // la précédente au lieu de s'y ajouter — pas de double comptage (CA-4).
        $stream = $this->stream(
            new RevenueRecognized($this->tenant(), '2026-08', 'PRJ-1', 78000, $this->at(), 'entry-1'),
            new RevenueRecognized($this->tenant(), '2026-08', 'PRJ-1', 90000, $this->at(), 'entry-1'),
        );

        self::assertSame([
            '2026-08' => ['PRJ-1' => 90000],
        ], RecognizedRevenue::byPeriodAndProject($stream));
    }

    public function testMixesSourcedAndStandaloneRecognitions(): void
    {
        $stream = $this->stream(
            new RevenueRecognized($this->tenant(), '2026-08', 'PRJ-1', 40000, $this->at(), 'entry-1'),
            new RevenueRecognized($this->tenant(), '2026-08', 'PRJ-1', 10000, $this->at()),
        );

        self::assertSame([
            '2026-08' => ['PRJ-1' => 50000],
        ], RecognizedRevenue::byPeriodAndProject($stream));
    }

    public function testIgnoresUnrelatedEventNames(): void
    {
        $stream = [new StoredEvent($this->otherEvent(), 1)];

        self::assertSame([], RecognizedRevenue::byPeriodAndProject($stream));
    }

    /**
     * @return list<StoredEvent>
     */
    private function stream(RevenueRecognized ...$events): array
    {
        $stored = [];
        $sequence = 0;
        foreach ($events as $event) {
            $stored[] = new StoredEvent($event, ++$sequence);
        }

        return $stored;
    }

    private function otherEvent(): \App\Domain\Analytics\DomainEvent
    {
        return new readonly class ($this->tenant()) implements \App\Domain\Analytics\DomainEvent {
            public function __construct(private TenantId $tenant)
            {
            }

            public function tenantId(): TenantId
            {
                return $this->tenant;
            }

            public function name(): string
            {
                return 'time_entry_validated';
            }

            public function occurredAt(): DateTimeImmutable
            {
                return new DateTimeImmutable();
            }

            public function payload(): array
            {
                return [];
            }
        };
    }

    private function tenant(): TenantId
    {
        return $this->tenant ??= TenantId::generate();
    }

    private function at(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-08-15 10:00:00');
    }
}
