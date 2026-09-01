<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Organization;

use App\Domain\Organization\OrgUnit;
use App\Domain\Tenant\TenantId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * US-010 (EF-REF-1/3) — unité organisationnelle : nœud d'une hiérarchie paramétrable,
 * porté par tenant, désactivable (jamais supprimé s'il est référencé — RG-REF-1).
 */
final class OrgUnitTest extends TestCase
{
    private const string TENANT = '018f9c4e-0000-7000-8000-000000000001';

    public function testRootUnitHasNoParentAndIsActiveByDefault(): void
    {
        $unit = new OrgUnit(TenantId::fromString(self::TENANT), null, 'Direction générale');

        self::assertTrue($unit->isRoot());
        self::assertTrue($unit->isActive());
        self::assertNull($unit->parentId());
        self::assertSame('Direction générale', $unit->name());
        self::assertSame(self::TENANT, $unit->tenantId()->toString());
    }

    public function testChildUnitReferencesItsParent(): void
    {
        $unit = new OrgUnit(TenantId::fromString(self::TENANT), 'parent-id', 'Équipe Data');

        self::assertFalse($unit->isRoot());
        self::assertSame('parent-id', $unit->parentId());
    }

    public function testNameCannotBeBlank(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new OrgUnit(TenantId::fromString(self::TENANT), null, '   ');
    }

    public function testRenameTrimsAndReplacesTheName(): void
    {
        $unit = new OrgUnit(TenantId::fromString(self::TENANT), null, 'Direction');
        $unit->rename('  Direction financière  ');

        self::assertSame('Direction financière', $unit->name());
    }

    public function testDeactivateAndReactivateToggleTheStatus(): void
    {
        $unit = new OrgUnit(TenantId::fromString(self::TENANT), null, 'Direction');

        $unit->deactivate();
        self::assertFalse($unit->isActive());

        $unit->activate();
        self::assertTrue($unit->isActive());
    }
}
