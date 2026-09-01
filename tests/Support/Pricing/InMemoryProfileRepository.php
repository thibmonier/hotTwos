<?php

declare(strict_types=1);

namespace App\Tests\Support\Pricing;

use App\Domain\Pricing\Profile;
use App\Domain\Pricing\ProfileRepository;
use App\Domain\Tenant\TenantId;

final class InMemoryProfileRepository implements ProfileRepository
{
    /** @var list<Profile> */
    public array $profiles = [];

    public function save(Profile $profile): void
    {
        foreach ($this->profiles as $existing) {
            if ($existing === $profile) {
                return;
            }
        }
        $this->profiles[] = $profile;
    }

    public function find(TenantId $tenant, string $id): ?Profile
    {
        foreach ($this->profiles as $profile) {
            if ($profile->tenantId()->equals($tenant) && $profile->id() === $id) {
                return $profile;
            }
        }

        return null;
    }

    public function findByTenant(TenantId $tenant): array
    {
        return array_values(array_filter(
            $this->profiles,
            static fn (Profile $profile): bool => $profile->tenantId()->equals($tenant),
        ));
    }
}
