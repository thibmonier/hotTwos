<?php

declare(strict_types=1);

namespace App\Application\Project;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\SecurityAuditLogger;
use App\Domain\Project\CommitmentStatus;
use App\Domain\Project\ExternalCommitment;
use App\Domain\Project\ExternalCommitmentRepository;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectException;
use App\Domain\Project\ProjectMilestone;
use App\Domain\Project\ProjectMilestoneRepository;
use App\Domain\Project\ProjectReopening;
use App\Domain\Project\ProjectReopeningRepository;
use App\Domain\Project\ProjectRepository;
use App\Domain\Project\MilestoneStatus;
use App\Domain\Timesheet\TimeEntryRepository;
use App\Domain\User\User;
use Psr\Clock\ClockInterface;
use DateTimeImmutable;

/**
 * Clôture opérationnelle d'un projet et réouverture exceptionnelle (US-038). La clôture est
 * **bloquée** s'il reste des imputations non validées (CA-6, RG-PRJ-5) ; les jalons non atteints et
 * engagements non soldés sont des **avertissements** exigeant une confirmation explicite (CA-4). La
 * réouverture suit un flux 4-eyes (demande chef de projet, approbation admin — CA-3).
 */
final readonly class ManageProjectClosure
{
    public function __construct(
        private Authorizer $authorizer,
        private ProjectRepository $projects,
        private TimeEntryRepository $entries,
        private ProjectMilestoneRepository $milestones,
        private ExternalCommitmentRepository $commitments,
        private ProjectReopeningRepository $reopenings,
        private SecurityAuditLogger $audit,
        private ClockInterface $clock,
    ) {
    }

    public function close(User $user, string $projectId, bool $confirmWarnings): void
    {
        $this->authorizer->ensureCan($user, Permission::EDIT_PROJECT);
        $project = $this->requireProject($user, $projectId);

        // Blocage dur (CA-6, RG-PRJ-5) : aucune imputation en attente de validation.
        $pending = $this->entries->findPendingForProjects($user->tenantId(), [$projectId]);
        if ([] !== $pending) {
            throw new ProjectException(sprintf('Clôture impossible : %d imputation(s) non validée(s) — validez ou rejetez les imputations en attente avant de clôturer (RG-PRJ-5).', count($pending)));
        }

        // Avertissements (CA-4) : jalons non atteints, engagements non soldés → confirmation explicite.
        $warnings = $this->warnings($user->tenantId(), $projectId);
        if ([] !== $warnings && !$confirmWarnings) {
            throw new ProjectException('Points en cours à confirmer avant clôture : '.implode(' ; ', $warnings).'.');
        }

        $project->close($user->id(), $this->clock->now());
        $this->projects->save($project);
        $this->audit->record('project_closed', $user->tenantId()->toString(), $user->getUserIdentifier(), ['project' => $project->code()]);
    }

    public function requestReopening(User $user, string $projectId, string $reason): ProjectReopening
    {
        $this->authorizer->ensureCan($user, Permission::EDIT_PROJECT);
        $project = $this->requireProject($user, $projectId);
        if (!$project->isClosed()) {
            throw new ProjectException('Le projet n\'est pas clôturé : aucune réouverture nécessaire.');
        }

        $reopening = new ProjectReopening($user->tenantId(), $projectId, $user->id(), $reason, $this->clock->now());
        $this->reopenings->save($reopening);
        $this->audit->record('project_reopening_requested', $user->tenantId()->toString(), $user->getUserIdentifier(), ['project' => $project->code()]);

        return $reopening;
    }

    public function approveReopening(User $admin, string $projectId, string $reopeningId, DateTimeImmutable $openUntil): void
    {
        $this->authorizer->ensureCan($admin, Permission::MANAGE_ORGANIZATION);
        $reopening = $this->reopenings->find($admin->tenantId(), $reopeningId);
        if (!$reopening instanceof ProjectReopening || $reopening->projectId() !== $projectId) {
            throw new ProjectException('Demande de réouverture introuvable.');
        }

        $reopening->approve($admin->id(), $this->clock->now(), $openUntil);
        $this->reopenings->save($reopening);
        $this->audit->record('project_reopening_approved', $admin->tenantId()->toString(), $admin->getUserIdentifier(), ['reopening' => $reopeningId]);
    }

    /**
     * @return list<string>
     */
    private function warnings(\App\Domain\Tenant\TenantId $tenant, string $projectId): array
    {
        $warnings = [];
        $unreached = array_filter(
            $this->milestones->findForProject($tenant, $projectId),
            static fn (ProjectMilestone $m): bool => MilestoneStatus::ATTEINT !== $m->status(),
        );
        if ([] !== $unreached) {
            $warnings[] = sprintf('%d jalon(s) non atteint(s)', count($unreached));
        }

        $unsettled = array_filter(
            $this->commitments->findForProject($tenant, $projectId),
            static fn (ExternalCommitment $c): bool => CommitmentStatus::SOLDE !== $c->status(),
        );
        if ([] !== $unsettled) {
            $warnings[] = sprintf('%d engagement(s) non soldé(s)', count($unsettled));
        }

        return $warnings;
    }

    private function requireProject(User $user, string $projectId): Project
    {
        $project = $this->projects->find($user->tenantId(), $projectId);
        if (!$project instanceof Project) {
            throw new ProjectException('Projet introuvable.');
        }

        return $project;
    }
}
