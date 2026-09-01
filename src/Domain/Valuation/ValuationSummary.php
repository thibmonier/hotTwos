<?php

declare(strict_types=1);

namespace App\Domain\Valuation;

use DateTimeImmutable;

/**
 * Agrégat de lecture du tableau de bord financier (US-060, T-060-06).
 *
 * Indicateurs à la maille tenant : avancement de la valorisation (valued/total), CA et coût
 * cumulés (centimes entiers, INV-2), fraîcheur (dernière valorisation). Objet de lecture pur,
 * sans logique — construit par le repository depuis une agrégation SQL.
 */
final readonly class ValuationSummary
{
    public function __construct(
        public int $total,
        public int $valued,
        public int $missingRate,
        public int $revenueCents,
        public int $costCents,
        public ?DateTimeImmutable $lastValuedAt,
    ) {
    }

    /** Marge brute cumulée = CA − coût (centimes). */
    public function marginCents(): int
    {
        return $this->revenueCents - $this->costCents;
    }

    public function hasIncompleteValuations(): bool
    {
        return $this->missingRate > 0;
    }
}
