<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Budget;

use App\Domain\Budget\ChargeLandingCalculator;
use PHPUnit\Framework\TestCase;

/**
 * US-036 (CA-1/CA-2/CA-3/CA-5, OBJ-2) — atterrissage charge (EAC = coût consommé / avancement physique)
 * et alerte de dérive **précoce** : dépassement projeté > 10 % ET consommation < 50 %.
 */
final class ChargeLandingCalculatorTest extends TestCase
{
    private const int BUDGET = 100_000_00; // 100 000 €

    private ChargeLandingCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new ChargeLandingCalculator();
    }

    public function testLandingOnTrackHasNoDrift(): void
    {
        // 30 000 € consommés à 30 % d'avancement → atterrissage = 100 000 € (pile le budget).
        $landing = $this->calculator->land(self::BUDGET, 30_000_00, 30);

        self::assertTrue($landing->available);
        self::assertSame(100_000_00, $landing->landingCostCents);
        self::assertEqualsWithDelta(0.0, $landing->overrunPercent, 0.001);
        self::assertEqualsWithDelta(30.0, $landing->consumptionPercent, 0.001);
        self::assertFalse($landing->isEarlyDrift);
    }

    public function testEarlyDriftWhenOverrunAbove10AndConsumptionBelow50(): void
    {
        // 40 000 € consommés à 25 % → atterrissage 160 000 € (+60 %), consommation 40 % (< 50 %).
        $landing = $this->calculator->land(self::BUDGET, 40_000_00, 25);

        self::assertSame(160_000_00, $landing->landingCostCents);
        self::assertEqualsWithDelta(60.0, $landing->overrunPercent, 0.001);
        self::assertEqualsWithDelta(40.0, $landing->consumptionPercent, 0.001);
        self::assertTrue($landing->isEarlyDrift);
    }

    public function testNoEarlyDriftWhenConsumptionReached50(): void
    {
        // 60 000 € consommés à 50 % → atterrissage 120 000 € (+20 %) mais consommation 60 % (≥ 50 %).
        $landing = $this->calculator->land(self::BUDGET, 60_000_00, 50);

        self::assertEqualsWithDelta(20.0, $landing->overrunPercent, 0.001);
        self::assertEqualsWithDelta(60.0, $landing->consumptionPercent, 0.001);
        self::assertFalse($landing->isEarlyDrift, 'Fenêtre OBJ-2 dépassée (consommation ≥ 50 %).');
    }

    public function testNoEarlyDriftWhenOverrunWithin10(): void
    {
        // 20 000 € consommés à 20 % → atterrissage 100 000 € (0 %), consommation 20 %.
        $landing = $this->calculator->land(self::BUDGET, 20_000_00, 20);

        self::assertFalse($landing->isEarlyDrift);
    }

    public function testUnavailableWithoutBudget(): void
    {
        $landing = $this->calculator->land(null, 30_000_00, 30);

        self::assertFalse($landing->available);
        self::assertNull($landing->landingCostCents);
        self::assertFalse($landing->isEarlyDrift);
    }

    public function testUnavailableWithoutProgress(): void
    {
        self::assertFalse($this->calculator->land(self::BUDGET, 30_000_00, null)->available);
        self::assertFalse($this->calculator->land(self::BUDGET, 30_000_00, 0)->available); // pas de division par zéro
    }
}
