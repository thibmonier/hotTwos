<?php

declare(strict_types=1);

namespace App\Application\Finance;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Budget\BudgetTrackingCalculator;
use App\Domain\Budget\MarginDriftThresholdProvider;
use App\Domain\Margin\MarginCalculator;
use App\Domain\Margin\ProjectMargin;
use App\Domain\Margin\ProjectMarginRepository;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectRepository;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;

/**
 * Tableau de bord finance consolidé (US-073, CA-1/CA-3) — read service gated.
 *
 * Consolide les **marges figées par projet** (US-071, déjà agrégées par période → perf O(projets),
 * pas de rejeu ni de recalcul de marge côté dashboard, ARC-6) en totaux tenant et ventilations par
 * client (dimension {@see Project::clientName()}) et par projet, plus le nombre de projets en dérive
 * financière (US-072). Accès gated par {@see Permission::VIEW_PROJECT_FINANCIALS} ; coût, marge et
 * comptage de dérive réservés à {@see Permission::VIEW_COLLABORATOR_COST} (HAB-1) et tracés (HAB-6).
 */
final readonly class ConsolidatedFinanceReport
{
    public function __construct(
        private Authorizer $authorizer,
        private ProjectMarginRepository $margins,
        private ProjectRepository $projects,
        private MarginCalculator $marginCalculator,
        private BudgetTrackingCalculator $budgetCalculator,
        private MarginDriftThresholdProvider $thresholds,
    ) {
    }

    public function forPeriod(User $user, ?string $period = null, ?string $clientFilter = null): FinanceDashboard
    {
        $this->authorizer->ensureCan($user, Permission::VIEW_PROJECT_FINANCIALS);

        $tenant = $user->tenantId();
        $availablePeriods = $this->margins->findPeriods($tenant);
        $effectivePeriod = $period ?? ($availablePeriods[0] ?? null);
        $costVisible = $this->authorizer->can($user, Permission::VIEW_COLLABORATOR_COST);

        if (null === $effectivePeriod) {
            return $this->empty($availablePeriods, $costVisible, $clientFilter);
        }

        if ($costVisible) {
            $this->authorizer->authorizeSensitiveRead($user, Permission::VIEW_COLLABORATOR_COST, 'finance:dashboard:'.$effectivePeriod);
        }

        $margins = $this->margins->findForPeriod($tenant, $effectivePeriod);
        $projectsById = $this->projectsById($tenant);
        $threshold = $this->thresholds->pointsFor($tenant);

        // Filtre client appliqué côté backend (CA-4).
        $selected = [];
        foreach ($margins as $margin) {
            $clientName = ($projectsById[$margin->projectRef()] ?? null)?->clientName() ?? 'Sans client';
            if (null !== $clientFilter && $clientName !== $clientFilter) {
                continue;
            }
            $selected[] = [$margin, $clientName];
        }

        $totalRevenue = 0;
        $totalCost = 0;
        $drifting = 0;
        $hasPartial = false;
        $byProject = [];
        /** @var array<string, array{revenue:int, cost:int, count:int}> $clientAgg */
        $clientAgg = [];

        foreach ($selected as [$margin, $clientName]) {
            /** @var ProjectMargin $margin */
            $totalRevenue += $margin->revenueCents();
            $totalCost += $margin->costCents();
            $hasPartial = $hasPartial || $margin->isPartial();

            $byProject[] = new FinanceProjectLine(
                $margin->projectRef(),
                $margin->projectName(),
                $clientName,
                $margin->revenueCents(),
                $costVisible ? $margin->costCents() : null,
                $costVisible ? $margin->marginCents() : null,
                $costVisible ? $this->marginCalculator->marginRatePercent($margin->revenueCents(), $margin->costCents()) : null,
                $margin->isPartial(),
            );

            $agg = $clientAgg[$clientName] ?? ['revenue' => 0, 'cost' => 0, 'count' => 0];
            $agg['revenue'] += $margin->revenueCents();
            $agg['cost'] += $margin->costCents();
            ++$agg['count'];
            $clientAgg[$clientName] = $agg;

            if ($costVisible && $this->isDrifting($projectsById[$margin->projectRef()] ?? null, $margin, $threshold)) {
                ++$drifting;
            }
        }

        return new FinanceDashboard(
            $effectivePeriod,
            [] !== $margins,
            $costVisible,
            $availablePeriods,
            $clientFilter,
            $totalRevenue,
            $costVisible ? $totalCost : null,
            $costVisible ? $totalRevenue - $totalCost : null,
            $costVisible ? $this->marginCalculator->marginRatePercent($totalRevenue, $totalCost) : null,
            count($byProject),
            $costVisible ? $drifting : null,
            $hasPartial,
            $this->clientLines($clientAgg, $costVisible),
            $byProject,
        );
    }

    private function isDrifting(?Project $project, ProjectMargin $margin, float $threshold): bool
    {
        if (!$project instanceof Project) {
            return false;
        }

        return $this->budgetCalculator->track(
            $project->budgetCents(),
            $project->revenueBudgetCents(),
            $margin->costCents(),
            $margin->revenueCents(),
            $threshold,
        )->isDrifting;
    }

    /**
     * @param array<string, array{revenue:int, cost:int, count:int}> $clientAgg
     *
     * @return list<FinanceClientLine>
     */
    private function clientLines(array $clientAgg, bool $costVisible): array
    {
        $lines = [];
        foreach ($clientAgg as $clientName => $agg) {
            $lines[] = new FinanceClientLine(
                (string) $clientName,
                $agg['count'],
                $agg['revenue'],
                $costVisible ? $agg['cost'] : null,
                $costVisible ? $agg['revenue'] - $agg['cost'] : null,
                $costVisible ? $this->marginCalculator->marginRatePercent($agg['revenue'], $agg['cost']) : null,
            );
        }
        usort($lines, static fn (FinanceClientLine $a, FinanceClientLine $b): int => $b->revenueCents <=> $a->revenueCents);

        return $lines;
    }

    /**
     * @return array<string, Project> projectId => Project
     */
    private function projectsById(TenantId $tenant): array
    {
        $map = [];
        foreach ($this->projects->findAllByTenant($tenant) as $project) {
            $map[$project->id()] = $project;
        }

        return $map;
    }

    /**
     * @param list<string> $availablePeriods
     */
    private function empty(array $availablePeriods, bool $costVisible, ?string $clientFilter): FinanceDashboard
    {
        return new FinanceDashboard(
            null,
            false,
            $costVisible,
            $availablePeriods,
            $clientFilter,
            0,
            $costVisible ? 0 : null,
            $costVisible ? 0 : null,
            null,
            0,
            $costVisible ? 0 : null,
            false,
            [],
            [],
        );
    }
}
