<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Authorization;

use App\Domain\Authorization\DataScope;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\Role;
use App\Domain\Tenant\TenantId;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

/**
 * US-003 — un rôle combine des permissions fonctionnelles et un périmètre de données.
 */
final class RoleTest extends TestCase
{
    public function testGrantsOnlyItsPermissions(): void
    {
        $role = new Role(
            TenantId::generate(),
            'Chef de projet',
            [Permission::VIEW_PROJECT, Permission::EDIT_PROJECT],
            DataScope::OWN_PROJECTS,
        );

        self::assertTrue($role->grants(Permission::VIEW_PROJECT));
        self::assertFalse($role->grants(Permission::VIEW_COLLABORATOR_COST));
        self::assertSame(DataScope::OWN_PROJECTS, $role->scope());
    }

    public function testDeduplicatesPermissions(): void
    {
        $role = new Role(
            TenantId::generate(),
            'Collaborateur',
            [Permission::VIEW_PROJECT, Permission::VIEW_PROJECT],
            DataScope::OWN,
        );

        self::assertCount(1, $role->permissions());
    }

    public function testRealignReplacesPermissionsAndScope(): void
    {
        $tenant = TenantId::generate();
        $role = new Role($tenant, 'Resource Manager', [Permission::VIEW_PROJECT], DataScope::OWN);

        $role->realignTo([Permission::VIEW_COLLABORATOR_COST], DataScope::POOL);

        self::assertTrue($role->grants(Permission::VIEW_COLLABORATOR_COST));
        self::assertFalse($role->grants(Permission::VIEW_PROJECT));
        self::assertSame(DataScope::POOL, $role->scope());
    }

    public function testRejectsEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Role(TenantId::generate(), '', [Permission::VIEW_PROJECT], DataScope::OWN);
    }
}
