<?php

declare(strict_types=1);

namespace App\Domain\Project;

/**
 * Dérive le **budget courant** d'un projet (US-033, EF-PRJ-8) = budget **initial** (porté par
 * {@see Project::budgetCents()} / {@see Project::revenueBudgetCents()}) + la somme des avenants. Aucun
 * effet sur les imputations/valorisations figées (INV-2/INV-3) : seule la cible budgétaire évolue.
 */
final class CurrentProjectBudget
{
    /**
     * @param iterable<BudgetAmendment> $amendments
     */
    public function current(?int $initialCostCents, ?int $initialRevenueCents, iterable $amendments): CurrentBudget
    {
        $costDelta = 0;
        $revenueDelta = 0;
        $hasCostDelta = false;
        $hasRevenueDelta = false;

        foreach ($amendments as $amendment) {
            $costDelta += $amendment->deltaCostCents();
            $revenueDelta += $amendment->deltaRevenueCents();
            $hasCostDelta = $hasCostDelta || 0 !== $amendment->deltaCostCents();
            $hasRevenueDelta = $hasRevenueDelta || 0 !== $amendment->deltaRevenueCents();
        }

        return new CurrentBudget(
            $this->apply($initialCostCents, $costDelta, $hasCostDelta),
            $this->apply($initialRevenueCents, $revenueDelta, $hasRevenueDelta),
        );
    }

    private function apply(?int $initial, int $delta, bool $hasDelta): ?int
    {
        if (null !== $initial) {
            return $initial + $delta;
        }

        // Pas de budget initial : le courant n'existe que si un avenant l'a réellement alimenté.
        return $hasDelta ? $delta : null;
    }
}
