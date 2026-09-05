<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Margin;

use App\Domain\Margin\MarginCalculator;
use PHPUnit\Framework\TestCase;

/**
 * US-071 (CA-3 / ARC-6) — moteur de marge unique : marge = CA − coût, taux de marge, borne CA = 0.
 */
final class MarginCalculatorTest extends TestCase
{
    private MarginCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new MarginCalculator();
    }

    public function testMarginIsRevenueMinusCost(): void
    {
        self::assertSame(4_200_00, $this->calculator->marginCents(10_000_00, 5_800_00));
    }

    public function testMarginCanBeNegative(): void
    {
        self::assertSame(-1_500_00, $this->calculator->marginCents(3_000_00, 4_500_00));
    }

    public function testMarginRateIsMarginOverRevenueInPercent(): void
    {
        // (10 000 − 5 800) / 10 000 = 42 %
        self::assertSame(42.0, $this->calculator->marginRatePercent(10_000_00, 5_800_00));
    }

    public function testMarginRateIsRoundedToTwoDecimals(): void
    {
        // (1 000 − 333) / 1 000 = 66,7 %
        self::assertSame(66.7, $this->calculator->marginRatePercent(1_000_00, 333_00));
    }

    public function testMarginRateIsNullWhenRevenueIsZero(): void
    {
        // Borne CA = 0 : pas de taux (jamais de division par zéro, ni 0 % trompeur).
        self::assertNull($this->calculator->marginRatePercent(0, 1_200_00));
    }

    public function testMarginRateIsNullWhenRevenueIsNegative(): void
    {
        self::assertNull($this->calculator->marginRatePercent(-500, 100));
    }
}
