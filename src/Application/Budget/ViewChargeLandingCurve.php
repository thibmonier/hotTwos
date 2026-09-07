<?php

declare(strict_types=1);

namespace App\Application\Budget;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Budget\ChargeLandingSnapshot;
use App\Domain\Budget\ChargeLandingSnapshotRepository;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectException;
use App\Domain\Project\ProjectRepository;
use App\Domain\User\User;

/**
 * US-079c (EF-PRJ-16) — lit la série de snapshots d'atterrissage d'un projet pour la courbe.
 *
 * Gating aligné sur {@see ViewProjectBudgetTracking} : `VIEW_PROJECT_FINANCIALS` requis ; les montants
 * de coût sont masqués sans `VIEW_COLLABORATOR_COST`.
 */
final readonly class ViewChargeLandingCurve
{
    public function __construct(
        private Authorizer $authorizer,
        private ProjectRepository $projects,
        private ChargeLandingSnapshotRepository $snapshots,
    ) {
    }

    public function forProject(User $user, string $projectId): ChargeLandingCurveView
    {
        $this->authorizer->ensureCan($user, Permission::VIEW_PROJECT_FINANCIALS);

        $tenant = $user->tenantId();
        $project = $this->projects->find($tenant, $projectId);
        if (!$project instanceof Project) {
            throw new ProjectException('Projet introuvable pour la courbe d\'atterrissage.');
        }

        $costVisible = $this->authorizer->can($user, Permission::VIEW_COLLABORATOR_COST);

        $points = array_map(
            static fn (ChargeLandingSnapshot $s): ChargeLandingCurvePoint => new ChargeLandingCurvePoint(
                $s->period(),
                $costVisible ? $s->landingCostCents() : null,
                $s->overrunPercent(),
                $s->physicalProgressPercent(),
                $s->isEarlyDrift(),
            ),
            $this->snapshots->findForProject($tenant, $projectId),
        );

        return new ChargeLandingCurveView($projectId, $project->name(), $costVisible, $points);
    }
}
