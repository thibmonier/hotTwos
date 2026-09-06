<?php

declare(strict_types=1);

namespace App\Domain\Budget;

/**
 * Calcule l'atterrissage en charge d'un projet et détecte une **dérive précoce** (US-036, OBJ-2).
 *
 * Atterrissage (EAC) = coût consommé / (avancement physique / 100). Le dépassement projeté est comparé
 * au budget de charge cible. L'alerte se déclenche **uniquement** si le dépassement dépasse 10 % ET que
 * la consommation reste sous 50 % (fenêtre OBJ-2 : détecter tôt, avant la moitié du budget consommé).
 * Avancement physique, RAF et consommation restent distincts (INV-4) : seul l'avancement pilote l'EAC.
 */
final class ChargeLandingCalculator
{
    /** Dépassement projeté (en %) au-delà duquel une dérive est signalée. */
    public const float OVERRUN_ALERT_PERCENT = 10.0;

    /** Consommation (en %) en-deçà de laquelle l'alerte est « précoce » (OBJ-2). */
    public const float EARLY_CONSUMPTION_GATE_PERCENT = 50.0;

    public function land(?int $costBudgetCents, int $consumedCostCents, ?int $physicalProgressPercent): ChargeLanding
    {
        // Indisponible sans budget charge ou sans avancement (évite toute division par zéro).
        if (null === $costBudgetCents || $costBudgetCents <= 0 || null === $physicalProgressPercent || $physicalProgressPercent <= 0) {
            return new ChargeLanding(false, null, $costBudgetCents, null, null, $physicalProgressPercent, false);
        }

        $landingCostCents = (int) round($consumedCostCents / ($physicalProgressPercent / 100));
        $overrunPercent = round(($landingCostCents - $costBudgetCents) / $costBudgetCents * 100, 2);
        $consumptionPercent = round($consumedCostCents / $costBudgetCents * 100, 2);

        $isEarlyDrift = $overrunPercent > self::OVERRUN_ALERT_PERCENT
            && $consumptionPercent < self::EARLY_CONSUMPTION_GATE_PERCENT;

        return new ChargeLanding(
            true,
            $landingCostCents,
            $costBudgetCents,
            $overrunPercent,
            $consumptionPercent,
            $physicalProgressPercent,
            $isEarlyDrift,
        );
    }
}
