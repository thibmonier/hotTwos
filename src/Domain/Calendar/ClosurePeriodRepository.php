<?php

declare(strict_types=1);

namespace App\Domain\Calendar;

use App\Domain\Tenant\TenantId;

interface ClosurePeriodRepository
{
    public function save(ClosurePeriod $closure): void;

    public function delete(ClosurePeriod $closure): void;

    public function find(TenantId $tenant, string $id): ?ClosurePeriod;

    /**
     * Fermetures du tenant, ordonnées par date de début.
     *
     * @return list<ClosurePeriod>
     */
    public function findForTenant(TenantId $tenant): array;
}
