<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Valuation;

use App\Domain\Tenant\TenantId;
use App\Domain\Valuation\TimeEntryValuation;
use App\Domain\Valuation\ValuationStatus;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

/**
 * US-060 (INV-2/INV-3) — valorisation figée d'une imputation : coût et revenu en centimes,
 * avec le taux appliqué copié (snapshot) au moment de la validation. Jamais recalculée ensuite.
 */
final class TimeEntryValuationTest extends TestCase
{
    private const string TENANT = '018f9c4e-0000-7000-8000-000000000001';
    private const string ENTRY = '018f9c4e-0000-7000-8000-0000000000ee';

    public function testValuedCarriesFrozenSnapshot(): void
    {
        $valuation = TimeEntryValuation::valued(
            TenantId::fromString(self::TENANT),
            self::ENTRY,
            costCents: 25714,
            revenueCents: 44571,
            snapshotCostRateCents: 45000,
            snapshotSellingRateCents: 78000,
            rateDate: $this->date('2026-01-01'),
            valuedAt: $this->at('2026-09-15 10:00:00'),
        );

        self::assertSame(ValuationStatus::VALUED, $valuation->status());
        self::assertSame(self::ENTRY, $valuation->timeEntryId());
        self::assertSame(25714, $valuation->costCents());
        self::assertSame(44571, $valuation->revenueCents());
        self::assertSame(45000, $valuation->snapshotCostRateCents());
        self::assertSame(78000, $valuation->snapshotSellingRateCents());
        self::assertEquals($this->date('2026-01-01'), $valuation->snapshotRateDate());
        self::assertSame(self::TENANT, $valuation->tenantId()->toString());
    }

    public function testMissingRateHasNoValueAndNoSnapshot(): void
    {
        $valuation = TimeEntryValuation::missingRate(
            TenantId::fromString(self::TENANT),
            self::ENTRY,
            $this->at('2026-09-15 10:00:00'),
        );

        self::assertSame(ValuationStatus::MISSING_RATE, $valuation->status());
        self::assertSame(0, $valuation->costCents());
        self::assertSame(0, $valuation->revenueCents());
        self::assertNull($valuation->snapshotCostRateCents());
        self::assertNull($valuation->snapshotRateDate());
    }

    private function date(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value.' 00:00:00', new DateTimeZone('UTC'));
    }

    private function at(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value, new DateTimeZone('UTC'));
    }
}
