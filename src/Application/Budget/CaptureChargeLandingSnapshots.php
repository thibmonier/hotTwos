<?php

declare(strict_types=1);

namespace App\Application\Budget;

use App\Domain\Budget\ChargeDriftThresholdProvider;
use App\Domain\Budget\ChargeLanding;
use App\Domain\Budget\ChargeLandingCalculator;
use App\Domain\Budget\ChargeLandingSnapshot;
use App\Domain\Budget\ChargeLandingSnapshotRepository;
use App\Domain\Project\BudgetAmendmentRepository;
use App\Domain\Project\CurrentProjectBudget;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectLotRepository;
use App\Domain\Project\ProjectProgressCalculator;
use App\Domain\Project\ProjectRepository;
use App\Domain\Tenant\TenantId;
use App\Domain\Valuation\TimeEntryValuationRepository;
use Psr\Clock\ClockInterface;

/**
 * US-079c (EF-PRJ-16) — capture l'atterrissage en charge de chaque projet à la clôture d'une période.
 *
 * Réutilise {@see ChargeLandingCalculator} (règle OBJ-2, aucune duplication) et la même dérivation que
 * {@see ViewProjectBudgetTracking} (budget courant + coût réalisé cumulé + avancement physique). N'a
 * **aucun** couplage au figeage de marge : {@see \App\Application\Margin\ComputeProjectMargins} reste
 * inchangé. Idempotent par (tenant, projet, période) via {@see ChargeLandingSnapshotRepository::replaceForPeriod()}.
 */
final readonly class CaptureChargeLandingSnapshots
{
    public function __construct(
        private ProjectRepository $projects,
        private TimeEntryValuationRepository $valuations,
        private BudgetAmendmentRepository $amendments,
        private CurrentProjectBudget $currentBudget,
        private ProjectLotRepository $lots,
        private ProjectProgressCalculator $progress,
        private ChargeLandingCalculator $landing,
        private ChargeDriftThresholdProvider $chargeDriftThresholds,
        private ChargeLandingSnapshotRepository $snapshots,
        private ClockInterface $clock,
    ) {
    }

    public function forClosedPeriod(TenantId $tenant, string $period): void
    {
        $realizedByProject = $this->realizedCostByProject($tenant);
        $capturedAt = $this->clock->now();

        $snapshots = [];
        foreach ($this->projects->findAllByTenant($tenant) as $project) {
            $landing = $this->landingFor($tenant, $project, $realizedByProject[$project->id()] ?? 0);
            if (!$landing->available) {
                continue;
            }

            $snapshots[] = ChargeLandingSnapshot::capture(
                $tenant,
                $period,
                $project->id(),
                $project->name(),
                $landing,
                $capturedAt,
            );
        }

        $this->snapshots->replaceForPeriod($tenant, $period, $snapshots);
    }

    private function landingFor(TenantId $tenant, Project $project, int $realizedCostCents): ChargeLanding
    {
        $current = $this->currentBudget->current(
            $project->budgetCents(),
            $project->revenueBudgetCents(),
            $this->amendments->findForProject($tenant, $project->id()),
        );

        $physicalProgress = $this->progress->weightedPhysicalProgress(
            $this->lots->findForProject($tenant, $project->id()),
        );

        $threshold = $this->chargeDriftThresholds->resolve($tenant, $project->contractType());

        return $this->landing->land($current->costCents, $realizedCostCents, $physicalProgress, $threshold);
    }

    /**
     * @return array<string, int>
     */
    private function realizedCostByProject(TenantId $tenant): array
    {
        $map = [];
        foreach ($this->valuations->projectBreakdownFor($tenant) as $line) {
            $map[$line->projectId] = $line->costCents;
        }

        return $map;
    }
}
