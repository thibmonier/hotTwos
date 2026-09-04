<?php

declare(strict_types=1);

namespace App\Domain\Valuation;

/**
 * US-060 (T-060-03) — occupation d'un collaborateur sur le mois de référence.
 *
 * Occupation = jours valorisés / capacité, où la **capacité** = jours ouvrés − absences validées
 * (même logique que {@see \App\Application\Completeness\CompletenessGrid}). Bornée à 100 % à
 * l'affichage : une capacité peut être dépassée (report, week-end travaillé) sans que « occupé »
 * dépasse le plein.
 */
final readonly class OccupationLine
{
    public function __construct(
        public string $userId,
        public int $valuedDays,
        public int $capacityDays,
    ) {
    }

    /**
     * Taux d'occupation en pourcentage entier, borné à 100 (capacité minimale de 1 jour pour éviter
     * une division par zéro sur un collaborateur totalement absent).
     */
    public function percent(): int
    {
        $capacity = max(1, $this->capacityDays);

        return (int) min(100, round($this->valuedDays / $capacity * 100));
    }
}
