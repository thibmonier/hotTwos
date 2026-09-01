<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

use App\Domain\Tenant\TenantId;

/**
 * Port de persistance des profils (US-011, DIP). Tenant passé explicitement.
 */
interface ProfileRepository
{
    public function save(Profile $profile): void;

    public function find(TenantId $tenant, string $id): ?Profile;

    /**
     * @return list<Profile>
     */
    public function findByTenant(TenantId $tenant): array;
}
