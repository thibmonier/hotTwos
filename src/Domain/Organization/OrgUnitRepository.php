<?php

declare(strict_types=1);

namespace App\Domain\Organization;

use App\Domain\Tenant\TenantId;

/**
 * Port de persistance des unités organisationnelles (US-010). Tenant passé explicitement.
 */
interface OrgUnitRepository
{
    public function save(OrgUnit $unit): void;

    public function find(TenantId $tenant, string $id): ?OrgUnit;
}
