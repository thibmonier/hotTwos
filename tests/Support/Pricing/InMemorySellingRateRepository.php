<?php

declare(strict_types=1);

namespace App\Tests\Support\Pricing;

use App\Domain\Pricing\RateScope;
use App\Domain\Pricing\SellingRate;
use App\Domain\Pricing\SellingRateRepository;
use App\Domain\Tenant\TenantId;

final class InMemorySellingRateRepository implements SellingRateRepository
{
    /** @var list<SellingRate> */
    public array $rates = [];

    public function save(SellingRate $rate): void
    {
        foreach ($this->rates as $existing) {
            if ($existing === $rate) {
                return;
            }
        }
        $this->rates[] = $rate;
    }

    public function findForScope(TenantId $tenant, string $profileId, RateScope $scope, string $scopeRefId): array
    {
        $matching = array_values(array_filter(
            $this->rates,
            static fn (SellingRate $r): bool => $r->tenantId()->equals($tenant)
                && $r->profileId() === $profileId
                && $r->scope() === $scope
                && $r->scopeRefId() === $scopeRefId,
        ));
        usort($matching, static fn (SellingRate $a, SellingRate $b): int => $a->period()->from() <=> $b->period()->from());

        return $matching;
    }
}
