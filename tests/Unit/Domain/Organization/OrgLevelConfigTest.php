<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Organization;

use App\Domain\Organization\OrgLevelConfig;
use App\Domain\Tenant\TenantId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * US-010 (EF-REF-1) — niveau hiérarchique nommé et paramétrable par le tenant
 * (de 1 à N niveaux, sans développement). La position ordonne les niveaux (1 = racine).
 */
final class OrgLevelConfigTest extends TestCase
{
    private const string TENANT = '018f9c4e-0000-7000-8000-000000000001';

    public function testLevelCarriesAPositionAndAName(): void
    {
        $level = new OrgLevelConfig(TenantId::fromString(self::TENANT), 1, 'Direction');

        self::assertSame(1, $level->position());
        self::assertSame('Direction', $level->name());
        self::assertSame(self::TENANT, $level->tenantId()->toString());
    }

    public function testPositionMustBeStrictlyPositive(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new OrgLevelConfig(TenantId::fromString(self::TENANT), 0, 'Direction');
    }

    public function testNameCannotBeBlank(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new OrgLevelConfig(TenantId::fromString(self::TENANT), 1, '  ');
    }

    public function testRenameReplacesTheName(): void
    {
        $level = new OrgLevelConfig(TenantId::fromString(self::TENANT), 2, 'Département');
        $level->rename('Business Unit');

        self::assertSame('Business Unit', $level->name());
    }
}
