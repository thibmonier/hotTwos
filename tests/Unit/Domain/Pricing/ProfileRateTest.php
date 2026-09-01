<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Pricing;

use App\Domain\Pricing\ProfileRate;
use App\Domain\Shared\EffectivePeriod;
use App\Domain\Tenant\TenantId;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * US-011 (EF-REF-5, INV-2) — entrée tarifaire historisée à date d'effet : coût de revient et
 * taux de vente en centimes entiers (jamais de flottant), sur une période de validité.
 */
final class ProfileRateTest extends TestCase
{
    private const string TENANT = '018f9c4e-0000-7000-8000-000000000001';
    private const string PROFILE = '018f9c4e-0000-7000-8000-0000000000cc';

    public function testRateCarriesProfilePeriodAndCents(): void
    {
        $period = EffectivePeriod::since($this->date('2026-01-01'));
        $rate = new ProfileRate(TenantId::fromString(self::TENANT), self::PROFILE, $period, 45000, 78000);

        self::assertSame(self::TENANT, $rate->tenantId()->toString());
        self::assertSame(self::PROFILE, $rate->profileId());
        self::assertSame(45000, $rate->costPriceCents());
        self::assertSame(78000, $rate->sellingPriceCents());
        self::assertTrue($rate->period()->isOpenEnded());
    }

    public function testPeriodIsReconstructed(): void
    {
        $period = EffectivePeriod::between($this->date('2026-01-01'), $this->date('2026-07-01'));
        $rate = new ProfileRate(TenantId::fromString(self::TENANT), self::PROFILE, $period, 45000, 78000);

        self::assertTrue($rate->period()->equals($period));
    }

    public function testNegativeCostIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ProfileRate(TenantId::fromString(self::TENANT), self::PROFILE, EffectivePeriod::since($this->date('2026-01-01')), -1, 78000);
    }

    public function testNegativeSellingPriceIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ProfileRate(TenantId::fromString(self::TENANT), self::PROFILE, EffectivePeriod::since($this->date('2026-01-01')), 45000, -1);
    }

    private function date(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value.' 00:00:00', new DateTimeZone('UTC'));
    }
}
