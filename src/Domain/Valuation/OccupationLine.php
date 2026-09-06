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
        /** Jours valorisés sur des projets **facturables** (US-032/RG-PRJ-6) ; `null` si non calculé. */
        public ?int $billableDays = null,
    ) {
    }

    /**
     * Taux d'occupation en pourcentage entier, borné à 100 (capacité minimale de 1 jour pour éviter
     * une division par zéro sur un collaborateur totalement absent).
     */
    public function percent(): int
    {
        return $this->ratio($this->valuedDays);
    }

    /** Taux d'occupation **facturable** (projets internes exclus, US-032) ; `null` si non calculé. */
    public function billablePercent(): ?int
    {
        return null === $this->billableDays ? null : $this->ratio($this->billableDays);
    }

    private function ratio(int $days): int
    {
        $capacity = max(1, $this->capacityDays);

        return (int) min(100, round($days / $capacity * 100));
    }
}
