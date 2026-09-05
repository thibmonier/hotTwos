<?php

declare(strict_types=1);

namespace App\Application\Finance;

/**
 * Ligne de rentabilité par client du tableau de bord consolidé (US-073, CA-1), **déjà gated**.
 *
 * Agrège les projets d'un même client (dimension {@see \App\Domain\Project\Project::clientName()}).
 * Coût, marge et taux de marge sont `null` sans habilitation coût (HAB-1).
 */
final readonly class FinanceClientLine
{
    public function __construct(
        public string $clientName,
        public int $projectCount,
        public int $revenueCents,
        public ?int $costCents,
        public ?int $marginCents,
        public ?float $marginRatePercent,
    ) {
    }
}
