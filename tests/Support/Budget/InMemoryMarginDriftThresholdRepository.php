<?php

declare(strict_types=1);

namespace App\Tests\Support\Budget;

use App\Domain\Budget\MarginDriftThreshold;
use App\Domain\Budget\MarginDriftThresholdRepository;
use App\Domain\Tenant\TenantId;

final class InMemoryMarginDriftThresholdRepository implements MarginDriftThresholdRepository
{
    /** @var list<MarginDriftThreshold> */
    public array $thresholds = [];

    public function findForTenant(TenantId $tenant): ?MarginDriftThreshold
    {
        foreach ($this->thresholds as $threshold) {
            if ($threshold->tenantId()->equals($tenant)) {
                return $threshold;
            }
        }

        return null;
    }

    public function save(MarginDriftThreshold $threshold): void
    {
        $this->thresholds = array_values(array_filter(
            $this->thresholds,
            static fn (MarginDriftThreshold $t): bool => !$t->tenantId()->equals($threshold->tenantId()),
        ));
        $this->thresholds[] = $threshold;
    }
}
