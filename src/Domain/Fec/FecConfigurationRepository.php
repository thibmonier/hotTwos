<?php

declare(strict_types=1);

namespace App\Domain\Fec;

use App\Domain\Tenant\TenantId;

/**
 * Port de persistance de la configuration comptable FEC (US-074, DIP). Une config par tenant.
 */
interface FecConfigurationRepository
{
    public function findForTenant(TenantId $tenant): ?FecConfiguration;

    public function save(FecConfiguration $configuration): void;
}
