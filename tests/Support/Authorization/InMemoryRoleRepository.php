<?php

declare(strict_types=1);

namespace App\Tests\Support\Authorization;

use App\Domain\Authorization\Role;
use App\Domain\Authorization\RoleRepository;
use App\Domain\Tenant\TenantId;

/**
 * Double de test du {@see RoleRepository} : rôles en mémoire, cloisonnés par tenant.
 */
final class InMemoryRoleRepository implements RoleRepository
{
    /** @var list<Role> */
    private array $roles = [];

    public function add(Role $role): void
    {
        $this->roles[] = $role;
    }

    public function findByNames(TenantId $tenant, array $names): array
    {
        return array_values(array_filter(
            $this->roles,
            static fn (Role $role): bool => $role->tenantId()->equals($tenant) && in_array($role->name(), $names, true),
        ));
    }

    public function findByName(TenantId $tenant, string $name): ?Role
    {
        foreach ($this->roles as $role) {
            if ($role->tenantId()->equals($tenant) && $role->name() === $name) {
                return $role;
            }
        }

        return null;
    }

    public function save(Role $role): void
    {
        $this->roles[] = $role;
    }
}
