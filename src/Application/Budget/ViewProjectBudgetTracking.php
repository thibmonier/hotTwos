<?php

declare(strict_types=1);

namespace App\Application\Budget;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Budget\BudgetTracking;
use App\Domain\Budget\BudgetTrackingCalculator;
use App\Domain\Budget\ChargeLanding;
use App\Domain\Budget\ChargeLandingCalculator;
use App\Domain\Budget\MarginDriftThresholdProvider;
use App\Domain\Project\BudgetAmendmentRepository;
use App\Domain\Project\CurrentProjectBudget;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectException;
use App\Domain\Project\ProjectLotRepository;
use App\Domain\Project\ProjectProgressCalculator;
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
        private ProjectLotRepository $lots,
        private ProjectProgressCalculator $progress,
        private ChargeLandingCalculator $landing,
        private BudgetAmendmentRepository $amendments,
        private CurrentProjectBudget $currentBudget,
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

        // US-033 : le suivi budgétaire compare le réalisé au **budget courant** (initial + Σ avenants).
        $current = $this->currentBudget->current(
            $project->budgetCents(),
            $project->revenueBudgetCents(),
            $this->amendments->findForProject($tenant, $projectId),
        );

        $realized = $this->realizedFor($tenant, $projectId);
        $tracking = $this->calculator->track(
            $current->costCents,
            $current->revenueCents,
            $realized->costCents,
            $realized->revenueCents,
            $this->thresholds->pointsFor($tenant),
        );

        // US-036 : atterrissage charge à partir de l'avancement physique agrégé des lots (US-035).
        $physicalProgress = $this->progress->weightedPhysicalProgress($this->lots->findForProject($tenant, $projectId));
        $landing = $this->landing->land($current->costCents, $realized->costCents, $physicalProgress);

        return $this->toView($projectId, $project->name(), $tracking, $landing, $costVisible);
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

    private function toView(string $projectId, string $projectName, BudgetTracking $t, ChargeLanding $landing, bool $costVisible): ProjectBudgetTrackingView
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
            // US-036 — atterrissage charge : ratios + alerte visibles dès VIEW_PROJECT_FINANCIALS ;
            // montants € et consommation restent réservés au coût visible (HAB-1, jamais de coût unitaire).
            $landing->available,
            $costVisible ? $landing->landingCostCents : null,
            $landing->overrunPercent,
            $costVisible ? $landing->consumptionPercent : null,
            $landing->physicalProgressPercent,
            $landing->isEarlyDrift,
        );
    }
}
