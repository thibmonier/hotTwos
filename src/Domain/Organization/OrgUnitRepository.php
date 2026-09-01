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

    /**
     * Toutes les unités du tenant (actives et désactivées), pour l'affichage de la hiérarchie.
     *
     * @return list<OrgUnit>
     */
    public function findByTenant(TenantId $tenant): array;
}
