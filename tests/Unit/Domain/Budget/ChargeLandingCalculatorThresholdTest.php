<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Budget;

use App\Domain\Budget\ChargeLandingCalculator;
use App\Domain\Budget\ResolvedChargeDriftThreshold;
use PHPUnit\Framework\TestCase;

/**
 * US-079b (EF-PRJ-15, CA-3/CA-4) — le seuil d'alerte et le 2e seuil (escalade direction) sont résolus
 * par type de projet ; sans seuils fournis, repli sur les constantes OBJ-2 (comportement S12 inchangé).
 */
final class ChargeLandingCalculatorThresholdTest extends TestCase
{
    private const int BUDGET = 100_000_00;

    private ChargeLandingCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new ChargeLandingCalculator();
    }

    public function testAlertUsesPerTypeThreshold(): void
    {
        // Atterrissage 110 000 € (+10 %), consommation 11 % : dérive selon un seuil « forfait » à 8 %…
        $forfait = $this->calculator->land(self::BUDGET, 11_000_00, 10, new ResolvedChargeDriftThreshold(8.0, 20.0));
        self::assertTrue($forfait->isEarlyDrift);

        // …mais pas selon un seuil « régie » à 15 % (10 % ≤ 15 %).
        $regie = $this->calculator->land(self::BUDGET, 11_000_00, 10, new ResolvedChargeDriftThreshold(15.0, 25.0));
        self::assertFalse($regie->isEarlyDrift);
    }

    public function testEscalationTriggeredOnSecondThreshold(): void
    {
        // Atterrissage 120 000 € (+20 %) : franchit le 2e seuil (escalade) à 15 %.
        $landing = $this->calculator->land(self::BUDGET, 24_000_00, 20, new ResolvedChargeDriftThreshold(8.0, 15.0));

        self::assertTrue($landing->isEscalated);
    }

    public function testNoEscalationBelowSecondThreshold(): void
    {
        // +20 % reste sous une escalade à 25 %.
        $landing = $this->calculator->land(self::BUDGET, 24_000_00, 20, new ResolvedChargeDriftThreshold(8.0, 25.0));

        self::assertFalse($landing->isEscalated);
    }

    public function testFallsBackToObj2ConstantsWithoutThreshold(): void
    {
        // Sans seuils fournis : alerte au-delà de 10 % (OBJ-2). +10 % pile n'alerte pas ; pas d'escalade.
        $landing = $this->calculator->land(self::BUDGET, 11_000_00, 10);

        self::assertFalse($landing->isEarlyDrift);
        self::assertFalse($landing->isEscalated);
    }
}
