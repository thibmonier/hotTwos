<?php

declare(strict_types=1);

namespace App\Application\Project;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\SecurityAuditLogger;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectException;
use App\Domain\Project\ProjectMilestone;
use App\Domain\Project\ProjectMilestoneRepository;
use App\Domain\Project\ProjectRepository;
use App\Domain\User\User;
use DateTimeImmutable;

/**
 * Gestion des jalons d'un projet (US-031, EF-PRJ-3). Habilitation `EDIT_PROJECT`. La date d'un jalon
 * doit tomber dans la période du projet (CA-7). L'atteinte d'un jalon à déclencheur de facturation
 * enregistre l'intention de façon **idempotente** ; l'émission réelle relève d'EPIC-005 (dégradé).
 */
final readonly class ManageMilestones
{
    public function __construct(
        private Authorizer $authorizer,
        private ProjectRepository $projects,
        private ProjectMilestoneRepository $milestones,
        private SecurityAuditLogger $audit,
    ) {
    }

    public function addMilestone(User $user, string $projectId, string $name, DateTimeImmutable $dueDate, ?int $billingTriggerCents): ProjectMilestone
    {
        $this->authorizer->ensureCan($user, Permission::EDIT_PROJECT);
        $tenant = $user->tenantId();

        $project = $this->projects->find($tenant, $projectId);
        if (!$project instanceof Project) {
            throw new ProjectException('Projet introuvable.');
        }
        $this->guardWithinPeriod($project, $dueDate);

        $milestone = new ProjectMilestone($tenant, $projectId, $name, $dueDate, $billingTriggerCents);
        $this->milestones->save($milestone);
        $this->audit->record('project_milestone_added', $tenant->toString(), $user->getUserIdentifier(), ['project' => $project->code(), 'milestone' => $milestone->id()]);

        return $milestone;
    }

    /** Marque le jalon atteint (idempotent sur la facturation — CA-7). */
    public function markReached(User $user, string $milestoneId, DateTimeImmutable $at): ProjectMilestone
    {
        $this->authorizer->ensureCan($user, Permission::EDIT_PROJECT);
        $tenant = $user->tenantId();

        $milestone = $this->milestones->find($tenant, $milestoneId);
        if (!$milestone instanceof ProjectMilestone) {
            throw new ProjectException('Jalon introuvable.');
        }

        $milestone->markReached($at);
        $this->milestones->save($milestone);
        $this->audit->record('project_milestone_reached', $tenant->toString(), $user->getUserIdentifier(), [
            'milestone' => $milestoneId,
            'billing' => $milestone->hasBillingTrigger() ? '1' : '0',
        ]);

        return $milestone;
    }

    private function guardWithinPeriod(Project $project, DateTimeImmutable $dueDate): void
    {
        $start = $project->startDate();
        $end = $project->endDate();
        if (($start instanceof DateTimeImmutable && $dueDate < $start) || ($end instanceof DateTimeImmutable && $dueDate > $end)) {
            throw new ProjectException(sprintf('La date du jalon (%s) est hors de la période du projet%s.', $dueDate->format('d/m/Y'), $start instanceof DateTimeImmutable && $end instanceof DateTimeImmutable ? sprintf(' (%s – %s)', $start->format('d/m/Y'), $end->format('d/m/Y')) : ''));
        }
    }
}
