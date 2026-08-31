<?php

declare(strict_types=1);

namespace App\Domain\Authorization;

/**
 * Descripteur d'un rôle de référence (US-003, CA-4) : la ligne de la matrice standard,
 * indépendante de tout tenant. Sert de source unique pour créer et réaligner les rôles.
 */
final readonly class RoleDefinition
{
    /**
     * @param non-empty-string $name
     * @param list<Permission> $permissions
     */
    public function __construct(
        public string $name,
        public array $permissions,
        public DataScope $scope,
    ) {
    }

    /**
     * Vrai si un rôle correspond exactement à cette définition (mêmes permissions, même
     * périmètre) — support de l'assertion de conformité à la matrice (CA-4).
     */
    public function matches(Role $role): bool
    {
        if ($role->scope() !== $this->scope) {
            return false;
        }

        $expected = array_map(static fn (Permission $permission): string => $permission->value, $this->permissions);
        $actual = array_map(static fn (Permission $permission): string => $permission->value, $role->permissions());
        sort($expected);
        sort($actual);

        return $expected === $actual;
    }
}
