<?php

declare(strict_types=1);

namespace App\Application\Authorization;

use App\Domain\Authorization\Role;
use App\Domain\Authorization\RoleRepository;
use App\Domain\Tenant\TenantId;

/**
 * Initialise (ou réaligne) la matrice de rôles standard d'un tenant (US-003, CA-4).
 *
 * Idempotent : un rôle absent est créé, un rôle présent est réaligné sur la définition
 * de référence — relancer l'opération ne crée jamais de doublon et converge toujours
 * vers la matrice.
 */
final readonly class InitializeDefaultRoles
{
    public function __construct(private RoleRepository $roles)
    {
    }

    public function forTenant(TenantId $tenant): void
    {
        foreach (DefaultRoleMatrix::definitions() as $definition) {
            $existing = $this->roles->findByName($tenant, $definition->name);

            if (!$existing instanceof Role) {
                $this->roles->save(new Role($tenant, $definition->name, $definition->permissions, $definition->scope));

                continue;
            }

            $existing->realignTo($definition->permissions, $definition->scope);
            $this->roles->save($existing);
        }
    }
}
