<?php

declare(strict_types=1);

namespace App\Domain\Organization;

use App\Domain\Tenant\TenantId;

/**
 * Port de persistance des rattachements historisés (US-010). Le tenant est toujours passé
 * explicitement : le repository ne lit aucun contexte ambiant (DIP, isolation vérifiable).
 */
interface OrgMembershipRepository
{
    public function save(OrgMembership $membership): void;

    /**
     * Historique des rattachements d'un collaborateur (toutes périodes), du plus récent au plus ancien.
     *
     * @return list<OrgMembership>
     */
    public function findForUser(TenantId $tenant, string $userId): array;

    /**
     * Rattachements référençant une unité (pour interdire sa suppression — RG-REF-1).
     *
     * @return list<OrgMembership>
     */
    public function findForOrgUnit(TenantId $tenant, string $orgUnitId): array;
}
