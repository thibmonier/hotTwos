<?php

declare(strict_types=1);

namespace App\Domain\Valuation;

/**
 * US-060 (T-060-03) — synthèse d'occupation du tenant sur un mois de référence.
 *
 * @see \App\Application\Valuation\OccupationReport
 */
final readonly class OccupationOverview
{
    /**
     * @param string               $referenceMonth mois « Y-m » sur lequel l'occupation est calculée
     * @param list<OccupationLine> $lines          une ligne par collaborateur ayant une activité valorisée
     */
    public function __construct(
        public string $referenceMonth,
        public array $lines,
    ) {
    }

    public function isEmpty(): bool
    {
        return [] === $this->lines;
    }
}
