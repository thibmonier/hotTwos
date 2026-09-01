<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Pricing;

use App\Domain\Pricing\CalculationMode;
use App\Domain\Pricing\Profile;
use App\Domain\Tenant\TenantId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * US-011 (EF-REF-4/20) — profil portant un mode de calcul du coût de revient
 * (direct / chargé / complet), porté par tenant, désactivable.
 */
final class ProfileTest extends TestCase
{
    private const string TENANT = '018f9c4e-0000-7000-8000-000000000001';

    public function testProfileCarriesNameAndCalculationModeAndIsActiveByDefault(): void
    {
        $profile = new Profile(TenantId::fromString(self::TENANT), 'Développeur senior', CalculationMode::LOADED);

        self::assertSame('Développeur senior', $profile->name());
        self::assertSame(CalculationMode::LOADED, $profile->calculationMode());
        self::assertTrue($profile->isActive());
        self::assertSame(self::TENANT, $profile->tenantId()->toString());
    }

    public function testNameCannotBeBlank(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Profile(TenantId::fromString(self::TENANT), '  ', CalculationMode::DIRECT);
    }

    public function testRenameAndModeCanBeChanged(): void
    {
        $profile = new Profile(TenantId::fromString(self::TENANT), 'Junior', CalculationMode::DIRECT);
        $profile->rename('Développeur junior');
        $profile->changeCalculationMode(CalculationMode::FULL);

        self::assertSame('Développeur junior', $profile->name());
        self::assertSame(CalculationMode::FULL, $profile->calculationMode());
    }

    public function testDeactivateAndReactivate(): void
    {
        $profile = new Profile(TenantId::fromString(self::TENANT), 'Consultant', CalculationMode::DIRECT);

        $profile->deactivate();
        self::assertFalse($profile->isActive());

        $profile->activate();
        self::assertTrue($profile->isActive());
    }
}
