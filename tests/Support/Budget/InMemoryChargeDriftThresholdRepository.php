<?php

declare(strict_types=1);

namespace App\Tests\Support\Budget;

use App\Domain\Budget\ChargeDriftThreshold;
use App\Domain\Budget\ChargeDriftThresholdRepository;
use App\Domain\Project\ContractType;
use App\Domain\Tenant\TenantId;

final class InMemoryChargeDriftThresholdRepository implements ChargeDriftThresholdRepository
{
    /** @var list<ChargeDriftThreshold> */
    public array $thresholds = [];

    public function findFor(TenantId $tenant, ContractType $type): ?ChargeDriftThreshold
    {
        foreach ($this->thresholds as $threshold) {
            if ($threshold->tenantId()->equals($tenant) && $threshold->contractType() === $type) {
                return $threshold;
            }
        }

        return null;
    }

    public function findForTenant(TenantId $tenant): array
    {
        return array_values(array_filter(
            $this->thresholds,
            static fn (ChargeDriftThreshold $t): bool => $t->tenantId()->equals($tenant),
        ));
    }

    public function save(ChargeDriftThreshold $threshold): void
    {
        foreach ($this->thresholds as $i => $existing) {
            if ($existing->tenantId()->equals($threshold->tenantId()) && $existing->contractType() === $threshold->contractType()) {
                $this->thresholds[$i] = $threshold;

                return;
            }
        }

        $this->thresholds[] = $threshold;
    }
}
