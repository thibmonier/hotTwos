<?php

declare(strict_types=1);

namespace App\Tests\Support\Pricing;

use App\Domain\Pricing\ProfileRate;
use App\Domain\Pricing\ProfileRateRepository;
use App\Domain\Tenant\TenantId;

final class InMemoryProfileRateRepository implements ProfileRateRepository
{
    /** @var list<ProfileRate> */
    public array $rates = [];

    public function save(ProfileRate $rate): void
    {
        foreach ($this->rates as $existing) {
            if ($existing === $rate) {
                return;
            }
        }
        $this->rates[] = $rate;
    }

    public function findForProfile(TenantId $tenant, string $profileId): array
    {
        $matching = array_values(array_filter(
            $this->rates,
            static fn (ProfileRate $rate): bool => $rate->tenantId()->equals($tenant) && $rate->profileId() === $profileId,
        ));

        usort($matching, static fn (ProfileRate $a, ProfileRate $b): int => $a->period()->from() <=> $b->period()->from());

        return $matching;
    }
}
