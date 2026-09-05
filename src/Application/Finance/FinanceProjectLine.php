<?php

declare(strict_types=1);

namespace App\Application\Finance;

/**
 * Ligne de rentabilité par projet du tableau de bord consolidé (US-073), **déjà gated**.
 *
 * Le CA reconnu est toujours présent ; coût, marge et taux de marge sont `null` sans habilitation
 * coût (HAB-1). `partial` reflète une valorisation incomplète de la marge figée (CA-4 d'US-071).
 */
final readonly class FinanceProjectLine
{
    public function __construct(
        public string $projectRef,
        public string $projectName,
        public string $clientName,
        public int $revenueCents,
        public ?int $costCents,
        public ?int $marginCents,
        public ?float $marginRatePercent,
        public bool $partial,
    ) {
    }
}
