<?php

declare(strict_types=1);

namespace App\Tests\Support\Organization;

use App\Domain\Organization\OrgUnit;
use App\Domain\Organization\OrgUnitRepository;
use App\Domain\Tenant\TenantId;

final class InMemoryOrgUnitRepository implements OrgUnitRepository
{
    /** @var list<OrgUnit> */
    public array $units = [];

    public function save(OrgUnit $unit): void
    {
        foreach ($this->units as $existing) {
            if ($existing === $unit) {
                return;
            }
        }
        $this->units[] = $unit;
    }

    public function find(TenantId $tenant, string $id): ?OrgUnit
    {
        foreach ($this->units as $unit) {
            if ($unit->tenantId()->equals($tenant) && $unit->id() === $id) {
                return $unit;
            }
        }

        return null;
    }
}
