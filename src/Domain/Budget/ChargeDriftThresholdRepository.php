<?php

declare(strict_types=1);

namespace App\Domain\Budget;

use App\Domain\Project\ContractType;
use App\Domain\Tenant\TenantId;

interface ChargeDriftThresholdRepository
{
    public function findFor(TenantId $tenant, ContractType $type): ?ChargeDriftThreshold;

    /**
     * @return list<ChargeDriftThreshold>
     */
    public function findForTenant(TenantId $tenant): array;

    public function save(ChargeDriftThreshold $threshold): void;
}
