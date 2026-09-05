<?php

declare(strict_types=1);

namespace App\Tests\Support\Margin;

use App\Domain\Margin\ProjectMargin;
use App\Domain\Margin\ProjectMarginRepository;
use App\Domain\Tenant\TenantId;

final class InMemoryProjectMarginRepository implements ProjectMarginRepository
{
    /** @var list<ProjectMargin> */
    public array $margins = [];

    public function replaceForPeriod(TenantId $tenant, string $period, array $margins): void
    {
        // Non-rétroactivité (INV-2) : seules les marges de (tenant, période) sont remplacées.
        $this->margins = array_values(array_filter(
            $this->margins,
            static fn (ProjectMargin $m): bool => !$m->tenantId()->equals($tenant) || $m->period() !== $period,
        ));

        foreach ($margins as $margin) {
            $this->margins[] = $margin;
        }
    }

    public function findForPeriod(TenantId $tenant, string $period): array
    {
        $own = array_values(array_filter(
            $this->margins,
            static fn (ProjectMargin $m): bool => $m->tenantId()->equals($tenant) && $m->period() === $period,
        ));
        usort($own, static fn (ProjectMargin $a, ProjectMargin $b): int => $b->revenueCents() <=> $a->revenueCents());

        return $own;
    }

    public function findPeriods(TenantId $tenant): array
    {
        $periods = [];
        foreach ($this->margins as $margin) {
            if ($margin->tenantId()->equals($tenant)) {
                $periods[$margin->period()] = true;
            }
        }
        $periods = array_keys($periods);
        rsort($periods);

        return $periods;
    }
}
