<?php

declare(strict_types=1);

namespace App\Domain\Budget;

use App\Domain\Margin\MarginCalculator;

/**
 * Moteur de rapprochement budget vs réalisé (US-072, T-072-01/02).
 *
 * Réutilise {@see MarginCalculator} (moteur de marge unique, ARC-6) pour la marge et le taux de marge,
 * cibles comme réalisés — aucune formule de marge dupliquée. Calcule écarts (absolus), consommation
 * budgétaire (coût réalisé / coût cible) et **dérive du taux de marge** (cible − réel, en points) ;
 * lève l'alerte quand la dérive défavorable dépasse le seuil. Pas de budget → pas d'écart ni de dérive
 * (CA-4), le réalisé reste exposé.
 */
final readonly class BudgetTrackingCalculator
{
    public function __construct(private MarginCalculator $margin)
    {
    }

    public function track(
        ?int $costBudgetCents,
        ?int $revenueBudgetCents,
        int $realizedCostCents,
        int $realizedRevenueCents,
        float $driftThresholdPoints,
    ): BudgetTracking {
        $hasBudget = null !== $costBudgetCents || null !== $revenueBudgetCents;

        $realizedMarginCents = $this->margin->marginCents($realizedRevenueCents, $realizedCostCents);
        $realizedMarginRate = $this->margin->marginRatePercent($realizedRevenueCents, $realizedCostCents);

        $targetMarginCents = null;
        $targetMarginRate = null;
        if (null !== $costBudgetCents && null !== $revenueBudgetCents) {
            $targetMarginCents = $this->margin->marginCents($revenueBudgetCents, $costBudgetCents);
            $targetMarginRate = $this->margin->marginRatePercent($revenueBudgetCents, $costBudgetCents);
        }

        $costVariance = null !== $costBudgetCents ? $realizedCostCents - $costBudgetCents : null;
        $revenueVariance = null !== $revenueBudgetCents ? $realizedRevenueCents - $revenueBudgetCents : null;
        $marginVariance = null !== $targetMarginCents ? $realizedMarginCents - $targetMarginCents : null;

        $consumption = (null !== $costBudgetCents && $costBudgetCents > 0)
            ? round($realizedCostCents / $costBudgetCents * 100, 2)
            : null;

        // Dérive défavorable : le taux de marge réel est sous la cible (cible − réel > 0).
        $driftPoints = (null !== $targetMarginRate && null !== $realizedMarginRate)
            ? round($targetMarginRate - $realizedMarginRate, 2)
            : null;
        $isDrifting = null !== $driftPoints && $driftPoints > $driftThresholdPoints;

        return new BudgetTracking(
            $hasBudget,
            $costBudgetCents,
            $revenueBudgetCents,
            $realizedCostCents,
            $realizedRevenueCents,
            $targetMarginCents,
            $targetMarginRate,
            $realizedMarginCents,
            $realizedMarginRate,
            $costVariance,
            $revenueVariance,
            $marginVariance,
            $consumption,
            $driftPoints,
            $driftThresholdPoints,
            $isDrifting,
        );
    }
}
