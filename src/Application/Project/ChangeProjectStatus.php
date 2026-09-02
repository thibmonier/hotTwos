<?php

declare(strict_types=1);

namespace App\Application\Project;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\SecurityAuditLogger;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectException;
use App\Domain\Project\ProjectRepository;
use App\Domain\Project\ProjectStatus;
use App\Domain\User\User;

/**
 * Transition du cycle de vie d'un projet (US-030, EF-PRJ-4). Habilitation `EDIT_PROJECT` (403 sinon).
 * Les transitions autorisées sont portées par {@see ProjectStatus::canTransitionTo()} ; une transition
 * invalide lève une {@see ProjectException} (422). La clôture (« Clôturé ») relève d'US-038 (prérequis).
 */
final readonly class ChangeProjectStatus
{
    public function __construct(
        private Authorizer $authorizer,
        private ProjectRepository $projects,
        private SecurityAuditLogger $audit,
    ) {
    }

    public function change(User $user, string $projectId, ProjectStatus $target): void
    {
        $this->authorizer->ensureCan($user, Permission::EDIT_PROJECT);
        $tenant = $user->tenantId();

        $project = $this->projects->find($tenant, $projectId);
        if (!$project instanceof Project) {
            throw new ProjectException('Projet introuvable.');
        }

        $project->changeStatus($target);
        $this->projects->save($project);

        $this->audit->record('project_status_changed', $tenant->toString(), $user->getUserIdentifier(), [
            'project' => $project->code(),
            'status' => $target->value,
        ]);
    }
}
