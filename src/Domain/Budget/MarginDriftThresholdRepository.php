<?php

declare(strict_types=1);

namespace App\Domain\Budget;

use App\Domain\Tenant\TenantId;

/**
 * Port de persistance du seuil de dérive paramétrable par tenant (US-018, DIP). Un par tenant.
 */
interface MarginDriftThresholdRepository
{
    public function findForTenant(TenantId $tenant): ?MarginDriftThreshold;

    public function save(MarginDriftThreshold $threshold): void;
}
