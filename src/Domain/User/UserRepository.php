<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Domain\Tenant\TenantId;

/**
 * Port de lecture des utilisateurs (DIP). Tenant passé explicitement : permet de valider
 * qu'un identifiant de collaborateur appartient bien au tenant courant (deny by default).
 */
interface UserRepository
{
    public function existsInTenant(TenantId $tenant, string $userId): bool;

    /**
     * Identifiants de tous les collaborateurs d'un tenant (périmètre « équipe » du pilotage — US-058).
     *
     * @return list<string>
     */
    public function findIdsByTenant(TenantId $tenant): array;
}
