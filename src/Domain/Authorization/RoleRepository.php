<?php

declare(strict_types=1);

namespace App\Domain\Authorization;

use App\Domain\Tenant\TenantId;

/**
 * Port de persistance des rôles (DIP — ARC-18). L'implémentation Doctrine vit en
 * infrastructure ; le domaine et l'application ne connaissent que ce contrat.
 *
 * Toutes les lectures sont implicitement cloisonnées par tenant (filtre US-001) ;
 * le `TenantId` passé en argument sert d'intention explicite et de garde-fou.
 */
interface RoleRepository
{
    /**
     * @param list<string> $names noms de rôles candidats (les inconnus sont ignorés)
     *
     * @return list<Role>
     */
    public function findByNames(TenantId $tenant, array $names): array;

    public function findByName(TenantId $tenant, string $name): ?Role;

    public function save(Role $role): void;
}
