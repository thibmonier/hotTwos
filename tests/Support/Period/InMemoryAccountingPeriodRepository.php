<?php

declare(strict_types=1);

namespace App\Tests\Support\Period;

use App\Domain\Period\AccountingPeriod;
use App\Domain\Period\AccountingPeriodRepository;
use App\Domain\Tenant\TenantId;

final class InMemoryAccountingPeriodRepository implements AccountingPeriodRepository
{
    /** @var list<AccountingPeriod> */
    public array $periods = [];

    public function save(AccountingPeriod $period): void
    {
        foreach ($this->periods as $existing) {
            if ($existing === $period) {
                return;
            }
        }
        $this->periods[] = $period;
    }

    public function findByPeriod(TenantId $tenant, string $period): ?AccountingPeriod
    {
        foreach ($this->periods as $existing) {
            if ($existing->tenantId()->equals($tenant) && $existing->period() === $period) {
                return $existing;
            }
        }

        return null;
    }

    public function findAllByTenant(TenantId $tenant): array
    {
        $own = array_values(array_filter(
            $this->periods,
            static fn (AccountingPeriod $p): bool => $p->tenantId()->equals($tenant),
        ));
        usort($own, static fn (AccountingPeriod $a, AccountingPeriod $b): int => $b->period() <=> $a->period());

        return $own;
    }
}
