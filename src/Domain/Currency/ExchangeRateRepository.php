<?php

declare(strict_types=1);

namespace App\Domain\Currency;

use App\Domain\Tenant\TenantId;

interface ExchangeRateRepository
{
    public function save(ExchangeRate $rate): void;

    /**
     * Taux d'une devise, triés par date d'effet.
     *
     * @return list<ExchangeRate>
     */
    public function findForCurrency(TenantId $tenant, string $code): array;
}
