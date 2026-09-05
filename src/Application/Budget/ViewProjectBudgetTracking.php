<?php

declare(strict_types=1);

namespace App\Application\Budget;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Budget\BudgetTracking;
use App\Domain\Budget\BudgetTrackingCalculator;
use App\Domain\Budget\MarginDriftThresholdProvider;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectException;
use App\Domain\Project\ProjectRepository;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Domain\Valuation\ProjectValuationLine;
use App\Domain\Valuation\TimeEntryValuationRepository;

/**
 * Lecture gated du suivi budgétaire d'un projet (US-072, T-072-01/03).
 *
 * Rapproche le budget prévisionnel du projet (coût cible `budget_cents`, CA cible `revenue_budget_cents`)
 * et le réalisé valorisé **à date** (coût/CA cumulés — {@see TimeEntryValuationRepository::projectBreakdownFor()}),
 * pour permettre de réagir avant la clôture. Accès gated par {@see Permission::VIEW_PROJECT_FINANCIALS} ;
 * coût, consommation, marge et dérive réservés à {@see Permission::VIEW_COLLABORATOR_COST} (HAB-1) et
 * tracés (HAB-6). Le calcul (marge, dérive) provient du moteur unique {@see BudgetTrackingCalculator}.
 */
final readonly class ViewProjectBudgetTracking
{
    public function __construct(
        private Authorizer $authorizer,
        private ProjectRepository $projects,
        private TimeEntryValuationRepository $valuations,
        private BudgetTrackingCalculator $calculator,
        private MarginDriftThresholdProvider $thresholds,
    ) {
    }

    public function forProject(User $user, string $projectId): ProjectBudgetTrackingView
    {
        $this->authorizer->ensureCan($user, Permission::VIEW_PROJECT_FINANCIALS);

        $tenant = $user->tenantId();
        $project = $this->projects->find($tenant, $projectId);
        if (!$project instanceof Project) {
            throw new ProjectException('Projet introuvable pour le suivi budgétaire.');
        }

        $costVisible = $this->authorizer->can($user, Permission::VIEW_COLLABORATOR_COST);
        if ($costVisible) {
            $this->authorizer->authorizeSensitiveRead($user, Permission::VIEW_COLLABORATOR_COST, 'budget:project:'.$projectId);
        }

        $realized = $this->realizedFor($tenant, $projectId);
        $tracking = $this->calculator->track(
            $project->budgetCents(),
            $project->revenueBudgetCents(),
            $realized->costCents,
            $realized->revenueCents,
            $this->thresholds->pointsFor($tenant),
        );

        return $this->toView($projectId, $project->name(), $tracking, $costVisible);
    }

    private function realizedFor(TenantId $tenant, string $projectId): ProjectValuationLine
    {
        foreach ($this->valuations->projectBreakdownFor($tenant) as $line) {
            if ($line->projectId === $projectId) {
                return $line;
            }
        }

        return new ProjectValuationLine($projectId, '', 0, 0, 0);
    }

    private function toView(string $projectId, string $projectName, BudgetTracking $t, bool $costVisible): ProjectBudgetTrackingView
    {
        return new ProjectBudgetTrackingView(
            $projectId,
            $projectName,
            $t->hasBudget,
            $costVisible,
            $t->revenueBudgetCents,
            $t->realizedRevenueCents,
            $t->revenueVarianceCents,
            $costVisible ? $t->costBudgetCents : null,
            $costVisible ? $t->realizedCostCents : null,
            $costVisible ? $t->costVarianceCents : null,
            $costVisible ? $t->consumptionPercent : null,
            $costVisible ? $t->targetMarginCents : null,
            $costVisible ? $t->targetMarginRatePercent : null,
            $costVisible ? $t->realizedMarginCents : null,
            $costVisible ? $t->realizedMarginRatePercent : null,
            $costVisible ? $t->marginVarianceCents : null,
            $costVisible ? $t->marginRateDriftPoints : null,
            $t->driftThresholdPoints,
            $costVisible && $t->isDrifting,
        );
    }
}
