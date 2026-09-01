<?php

declare(strict_types=1);

namespace App\Domain\Period;

use App\Domain\Tenant\TenantId;

/**
 * Port de persistance des périodes comptables (US-057, DIP). Tenant explicite.
 */
interface AccountingPeriodRepository
{
    public function save(AccountingPeriod $period): void;

    public function findByPeriod(TenantId $tenant, string $period): ?AccountingPeriod;
}
