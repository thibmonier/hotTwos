<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Budget;

use App\Domain\Budget\BudgetTrackingCalculator;
use App\Domain\Margin\MarginCalculator;
use PHPUnit\Framework\TestCase;

/**
 * US-072 (T-072-01/02, CA-1/CA-2/CA-4) — rapprochement budget vs réalisé : écarts, consommation,
 * dérive du taux de marge vs seuil, et absence de budget.
 */
final class BudgetTrackingCalculatorTest extends TestCase
{
    private BudgetTrackingCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new BudgetTrackingCalculator(new MarginCalculator());
    }

    public function testComparesBudgetToActualWithVariancesAndConsumption(): void
    {
        // Cible : coût 40 000 / CA 60 000 (marge 20 000, 33,33 %). Réel : coût 30 000 / CA 42 000.
        $t = $this->calculator->track(40_000_00, 60_000_00, 30_000_00, 42_000_00, 5.0);

        self::assertTrue($t->hasBudget);
        self::assertSame(20_000_00, $t->targetMarginCents);
        self::assertSame(33.33, $t->targetMarginRatePercent);
        self::assertSame(12_000_00, $t->realizedMarginCents);
        self::assertSame(28.57, $t->realizedMarginRatePercent);
        self::assertSame(-10_000_00, $t->costVarianceCents);   // 30 000 − 40 000
        self::assertSame(-18_000_00, $t->revenueVarianceCents); // 42 000 − 60 000
        self::assertSame(-8_000_00, $t->marginVarianceCents);   // 12 000 − 20 000
        self::assertSame(75.0, $t->consumptionPercent);         // 30 000 / 40 000
    }

    public function testDriftBelowThresholdDoesNotAlert(): void
    {
        // Dérive = 33,33 − 28,57 = 4,76 pts < seuil 5.
        $t = $this->calculator->track(40_000_00, 60_000_00, 30_000_00, 42_000_00, 5.0);

        self::assertSame(4.76, $t->marginRateDriftPoints);
        self::assertFalse($t->isDrifting);
    }

    public function testDriftAboveThresholdAlerts(): void
    {
        // Réel : coût 33 000 / CA 42 000 → taux 21,43 % ; dérive 33,33 − 21,43 = 11,90 pts > 5.
        $t = $this->calculator->track(40_000_00, 60_000_00, 33_000_00, 42_000_00, 5.0);

        self::assertSame(11.9, $t->marginRateDriftPoints);
        self::assertTrue($t->isDrifting);
    }

    public function testNoBudgetDisablesComparisonButKeepsRealized(): void
    {
        $t = $this->calculator->track(null, null, 30_000_00, 42_000_00, 5.0);

        self::assertFalse($t->hasBudget);
        self::assertNull($t->costVarianceCents);
        self::assertNull($t->revenueVarianceCents);
        self::assertNull($t->marginVarianceCents);
        self::assertNull($t->consumptionPercent);
        self::assertNull($t->marginRateDriftPoints);
        self::assertFalse($t->isDrifting);
        // Le réalisé reste calculé (CA-4).
        self::assertSame(12_000_00, $t->realizedMarginCents);
    }

    public function testCostBudgetOnlyGivesConsumptionButNoMarginDrift(): void
    {
        $t = $this->calculator->track(40_000_00, null, 30_000_00, 42_000_00, 5.0);

        self::assertTrue($t->hasBudget);
        self::assertSame(-10_000_00, $t->costVarianceCents);
        self::assertSame(75.0, $t->consumptionPercent);
        self::assertNull($t->targetMarginRatePercent);
        self::assertNull($t->marginRateDriftPoints);
        self::assertFalse($t->isDrifting);
    }
}
