<?php

declare(strict_types=1);

namespace App\Application\Budget;

/**
 * Vue « Suivi budgétaire » d'un projet, **déjà gated** pour l'affichage (US-072, T-072-03, HAB-1).
 *
 * Le CA (cible, réalisé, écart) relève de {@see \App\Domain\Authorization\Permission::VIEW_PROJECT_FINANCIALS}.
 * Le coût, la consommation budgétaire, la marge et la dérive sont réservés à
 * {@see \App\Domain\Authorization\Permission::VIEW_COLLABORATOR_COST} (HAB-1) : `null` quand le lecteur
 * n'y a pas droit (et `isDrifting` = false). `hasBudget` distingue le cas « aucun budget défini » (CA-4).
 */
final readonly class ProjectBudgetTrackingView
{
    public function __construct(
        public string $projectId,
        public string $projectName,
        public bool $hasBudget,
        public bool $costVisible,
        public ?int $revenueBudgetCents,
        public int $realizedRevenueCents,
        public ?int $revenueVarianceCents,
        public ?int $costBudgetCents,
        public ?int $realizedCostCents,
        public ?int $costVarianceCents,
        public ?float $consumptionPercent,
        public ?int $targetMarginCents,
        public ?float $targetMarginRatePercent,
        public ?int $realizedMarginCents,
        public ?float $realizedMarginRatePercent,
        public ?int $marginVarianceCents,
        public ?float $marginRateDriftPoints,
        public float $driftThresholdPoints,
        public bool $isDrifting,
    ) {
    }
}
