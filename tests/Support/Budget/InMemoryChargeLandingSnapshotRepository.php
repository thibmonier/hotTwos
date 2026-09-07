<?php

declare(strict_types=1);

namespace App\Tests\Support\Budget;

use App\Domain\Budget\ChargeLandingSnapshot;
use App\Domain\Budget\ChargeLandingSnapshotRepository;
use App\Domain\Tenant\TenantId;

final class InMemoryChargeLandingSnapshotRepository implements ChargeLandingSnapshotRepository
{
    /** @var list<ChargeLandingSnapshot> */
    public array $snapshots = [];

    public function replaceForPeriod(TenantId $tenant, string $period, array $snapshots): void
    {
        $this->snapshots = array_values(array_filter(
            $this->snapshots,
            static fn (ChargeLandingSnapshot $s): bool => !$s->tenantId()->equals($tenant) || $s->period() !== $period,
        ));

        foreach ($snapshots as $snapshot) {
            $this->snapshots[] = $snapshot;
        }
    }

    public function findForProject(TenantId $tenant, string $projectId): array
    {
        $found = array_values(array_filter(
            $this->snapshots,
            static fn (ChargeLandingSnapshot $s): bool => $s->tenantId()->equals($tenant) && $s->projectId() === $projectId,
        ));
        usort($found, static fn (ChargeLandingSnapshot $a, ChargeLandingSnapshot $b): int => $a->period() <=> $b->period());

        return $found;
    }
}
