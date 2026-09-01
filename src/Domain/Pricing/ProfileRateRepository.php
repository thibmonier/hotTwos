<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

use App\Domain\Tenant\TenantId;

/**
 * Port de persistance des entrées tarifaires historisées (US-011, DIP).
 */
interface ProfileRateRepository
{
    public function save(ProfileRate $rate): void;

    /**
     * Toutes les entrées tarifaires d'un profil, par date d'effet croissante.
     *
     * @return list<ProfileRate>
     */
    public function findForProfile(TenantId $tenant, string $profileId): array;
}
