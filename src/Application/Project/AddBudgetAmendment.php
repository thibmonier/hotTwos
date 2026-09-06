<?php

declare(strict_types=1);

namespace App\Application\Project;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\SecurityAuditLogger;
use App\Domain\Project\BudgetAmendment;
use App\Domain\Project\BudgetAmendmentRepository;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectException;
use App\Domain\Project\ProjectRepository;
use App\Domain\User\User;
use Psr\Clock\ClockInterface;

/**
 * Ajoute un avenant budgétaire à un projet (US-033, EF-PRJ-8). Habilitation `EDIT_PROJECT` ; motif
 * obligatoire (RG-PRJ-4) ; refusé sur projet clôturé (RG-PRJ-5). Trace la révision (HAB-6). Les
 * imputations historiques ne sont pas touchées (INV-2/INV-3) : seule la cible budgétaire évolue.
 */
final readonly class AddBudgetAmendment
{
    public function __construct(
        private Authorizer $authorizer,
        private ProjectRepository $projects,
        private BudgetAmendmentRepository $amendments,
        private SecurityAuditLogger $audit,
        private ClockInterface $clock,
    ) {
    }

    public function add(User $user, string $projectId, int $deltaCostCents, int $deltaRevenueCents, string $reason): BudgetAmendment
    {
        $this->authorizer->ensureCan($user, Permission::EDIT_PROJECT);
        $tenant = $user->tenantId();

        $project = $this->projects->find($tenant, $projectId);
        if (!$project instanceof Project) {
            throw new ProjectException('Projet introuvable.');
        }
        $project->assertModifiable();

        $amendment = BudgetAmendment::record($tenant, $projectId, $deltaCostCents, $deltaRevenueCents, $reason, $user->id(), $this->clock->now());
        $this->amendments->save($amendment);

        $this->audit->record('project_budget_amended', $tenant->toString(), $user->getUserIdentifier(), [
            'project' => $project->code(),
            'delta_cost_cents' => (string) $deltaCostCents,
            'delta_revenue_cents' => (string) $deltaRevenueCents,
            'reason' => trim($reason),
        ]);

        return $amendment;
    }
}
