<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Pricing;

use App\Domain\Pricing\LoadedCostCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * US-011 (CA-2) — coût « chargé » journalier : (brut annuel × (1 + taux de charge)) / jours ouvrés,
 * en centimes entiers (jamais de flottant stocké).
 */
final class LoadedCostCalculatorTest extends TestCase
{
    public function testComputesDailyChargedCostInCents(): void
    {
        $calculator = new LoadedCostCalculator();

        // 60 000 € brut annuel (6 000 000 cts), charge 45 % (4500 bp), 218 jours ouvrés.
        // 6 000 000 × 1,45 / 218 = 39 908 cts (arrondi).
        self::assertSame(39908, $calculator->dailyCostCents(6_000_000, 4500));
    }

    public function testZeroChargeRateIsGrossOverWorkingDays(): void
    {
        $calculator = new LoadedCostCalculator();

        // 43 600 € (4 360 000 cts) / 218 = 20 000 cts pile.
        self::assertSame(20000, $calculator->dailyCostCents(4_360_000, 0));
    }

    public function testNegativeGrossIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new LoadedCostCalculator()->dailyCostCents(-1, 4500);
    }

    public function testNegativeChargeRateIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new LoadedCostCalculator()->dailyCostCents(6_000_000, -1);
    }
}
