<?php

declare(strict_types=1);

namespace App\Domain\Absence;

use App\Domain\Tenant\TenantId;

/**
 * Port du référentiel des types d'absence (US-054, DIP). Tenant explicite.
 */
interface AbsenceTypeRepository
{
    public function save(AbsenceType $type): void;

    public function findById(TenantId $tenant, string $id): ?AbsenceType;

    /**
     * @return list<AbsenceType>
     */
    public function findAllByTenant(TenantId $tenant): array;
}
