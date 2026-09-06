<?php

declare(strict_types=1);

namespace App\Domain\Currency;

use App\Domain\Tenant\TenantId;

interface ReferenceCurrencyRepository
{
    public function findForTenant(TenantId $tenant): ?ReferenceCurrency;

    public function save(ReferenceCurrency $reference): void;
}
