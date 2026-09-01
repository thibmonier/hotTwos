<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

use App\Domain\Tenant\TenantId;

/**
 * Port de persistance des rattachements collaborateur → profil (US-011/US-060, DIP).
 */
interface ProfileAssignmentRepository
{
    public function save(ProfileAssignment $assignment): void;

    /**
     * Historique des rattachements d'un collaborateur (toutes périodes).
     *
     * @return list<ProfileAssignment>
     */
    public function findForUser(TenantId $tenant, string $userId): array;
}
