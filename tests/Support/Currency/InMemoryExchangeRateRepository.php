<?php

declare(strict_types=1);

namespace App\Tests\Support\Currency;

use App\Domain\Currency\ExchangeRate;
use App\Domain\Currency\ExchangeRateRepository;
use App\Domain\Tenant\TenantId;

final class InMemoryExchangeRateRepository implements ExchangeRateRepository
{
    /** @var list<ExchangeRate> */
    public array $rates = [];

    public function save(ExchangeRate $rate): void
    {
        foreach ($this->rates as $existing) {
            if ($existing === $rate) {
                return;
            }
        }
        $this->rates[] = $rate;
    }

    public function findForCurrency(TenantId $tenant, string $code): array
    {
        $matching = array_values(array_filter(
            $this->rates,
            static fn (ExchangeRate $r): bool => $r->tenantId()->equals($tenant) && $r->code() === strtoupper($code),
        ));
        usort($matching, static fn (ExchangeRate $a, ExchangeRate $b): int => $a->period()->from() <=> $b->period()->from());

        return $matching;
    }
}
