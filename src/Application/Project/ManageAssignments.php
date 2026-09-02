<?php

declare(strict_types=1);

namespace App\Application\Project;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\SecurityAuditLogger;
use App\Domain\Project\ExceptionalImputationOpening;
use App\Domain\Project\ExceptionalImputationOpeningRepository;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectAssignment;
use App\Domain\Project\ProjectAssignmentRepository;
use App\Domain\Project\ProjectException;
use App\Domain\Project\ProjectRepository;
use App\Domain\User\User;
use Psr\Clock\ClockInterface;
use DateTimeImmutable;

/**
 * Affectation de collaborateurs à un projet et ouvertures exceptionnelles d'imputation (US-037).
 * Habilitation `EDIT_PROJECT` (chef de projet / resource manager — 403 sinon). L'ouverture
 * exceptionnelle est tracée (auteur, motif) et bornée à une semaine (CA-2).
 */
final readonly class ManageAssignments
{
    public function __construct(
        private Authorizer $authorizer,
        private ProjectRepository $projects,
        private ProjectAssignmentRepository $assignments,
        private ExceptionalImputationOpeningRepository $openings,
        private SecurityAuditLogger $audit,
        private ClockInterface $clock,
    ) {
    }

    public function assign(User $user, string $projectId, string $userId, string $role, int $plannedDays, ?DateTimeImmutable $startDate, ?DateTimeImmutable $endDate): ProjectAssignment
    {
        $this->authorizer->ensureCan($user, Permission::EDIT_PROJECT);
        $project = $this->requireProject($user, $projectId);

        $assignment = new ProjectAssignment($project->tenantId(), $projectId, $userId, $role, $plannedDays, $startDate, $endDate);
        $this->assignments->save($assignment);
        $this->audit->record('project_assignment_added', $project->tenantId()->toString(), $user->getUserIdentifier(), ['project' => $project->code(), 'user' => $userId]);

        return $assignment;
    }

    public function remove(User $user, string $assignmentId): void
    {
        $this->authorizer->ensureCan($user, Permission::EDIT_PROJECT);
        $assignment = $this->assignments->find($user->tenantId(), $assignmentId);
        if (!$assignment instanceof ProjectAssignment) {
            throw new ProjectException('Affectation introuvable.');
        }
        $this->assignments->remove($assignment);
        $this->audit->record('project_assignment_removed', $user->tenantId()->toString(), $user->getUserIdentifier(), ['assignment' => $assignmentId]);
    }

    public function grantOpening(User $user, string $projectId, string $userId, DateTimeImmutable $weekStart, string $reason): ExceptionalImputationOpening
    {
        $this->authorizer->ensureCan($user, Permission::EDIT_PROJECT);
        $project = $this->requireProject($user, $projectId);

        $opening = new ExceptionalImputationOpening($project->tenantId(), $projectId, $userId, $weekStart, $reason, $user->id(), $this->clock->now());
        $this->openings->save($opening);
        $this->audit->record('project_exceptional_opening', $project->tenantId()->toString(), $user->getUserIdentifier(), [
            'project' => $project->code(),
            'user' => $userId,
            'week' => $opening->weekStart()->format('Y-m-d'),
        ]);

        return $opening;
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
