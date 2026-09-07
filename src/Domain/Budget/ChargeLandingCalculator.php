<?php

declare(strict_types=1);

namespace App\Domain\Budget;

final class ChargeLandingCalculator
{
    public const float OVERRUN_ALERT_PERCENT = 10.0;

    public const float EARLY_CONSUMPTION_GATE_PERCENT = 50.0;

    /**
     * US-079b — le seuil d'alerte et le seuil d'escalade sont désormais résolus par type de projet
     * ({@see ResolvedChargeDriftThreshold}). Sans seuils fournis, on retombe sur les constantes OBJ-2
     * (compat ascendante — comportement S12 inchangé).
     */
    public function land(
        ?int $costBudgetCents,
        int $consumedCostCents,
        ?int $physicalProgressPercent,
        ?ResolvedChargeDriftThreshold $threshold = null,
    ): ChargeLanding {
        $threshold ??= ResolvedChargeDriftThreshold::default();

        if (null === $costBudgetCents || $costBudgetCents <= 0 || null === $physicalProgressPercent || $physicalProgressPercent <= 0) {
            return new ChargeLanding(false, null, $costBudgetCents, null, null, $physicalProgressPercent, false);
        }

        $landingCostCents = (int) round($consumedCostCents / ($physicalProgressPercent / 100));
        $overrunPercent = round(($landingCostCents - $costBudgetCents) / $costBudgetCents * 100, 2);
        $consumptionPercent = round($consumedCostCents / $costBudgetCents * 100, 2);

        $isEarlyDrift = $overrunPercent > $threshold->alertPercent
            && $consumptionPercent < self::EARLY_CONSUMPTION_GATE_PERCENT;
        $isEscalated = $overrunPercent > $threshold->escalationPercent;

        return new ChargeLanding(
            true,
            $landingCostCents,
            $costBudgetCents,
            $overrunPercent,
            $consumptionPercent,
            $physicalProgressPercent,
            $isEarlyDrift,
            $isEscalated,
        );
    }
}
