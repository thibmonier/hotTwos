<?php

declare(strict_types=1);

namespace App\Domain\Budget;

/**
 * Rapprochement budget prévisionnel vs réalisé valorisé d'un projet (US-072, CA-1/CA-2).
 *
 * Toutes les valeurs monétaires sont en centimes entiers. Les cibles (`*Budget*`, `target*`) sont
 * `null` quand le projet n'a pas de budget correspondant (CA-4) : dans ce cas écarts, consommation et
 * dérive ne sont pas calculés. La dérive (`isDrifting`) porte sur le **taux de marge** (réel vs cible),
 * en points de pourcentage, distincte de la dérive de charge (US-036).
 */
final readonly class BudgetTracking
{
    public function __construct(
        public bool $hasBudget,
        public ?int $costBudgetCents,
        public ?int $revenueBudgetCents,
        public int $realizedCostCents,
        public int $realizedRevenueCents,
        public ?int $targetMarginCents,
        public ?float $targetMarginRatePercent,
        public int $realizedMarginCents,
        public ?float $realizedMarginRatePercent,
        public ?int $costVarianceCents,
        public ?int $revenueVarianceCents,
        public ?int $marginVarianceCents,
        public ?float $consumptionPercent,
        public ?float $marginRateDriftPoints,
        public float $driftThresholdPoints,
        public bool $isDrifting,
    ) {
    }
}
