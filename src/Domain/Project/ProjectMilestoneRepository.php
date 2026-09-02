<?php

declare(strict_types=1);

namespace App\Domain\Project;

use App\Domain\Tenant\TenantId;

/**
 * Port de persistance des jalons de projet (US-031, DIP). Tenant explicite.
 */
interface ProjectMilestoneRepository
{
    public function save(ProjectMilestone $milestone): void;

    public function find(TenantId $tenant, string $milestoneId): ?ProjectMilestone;

    /**
     * @return list<ProjectMilestone> jalons du projet, par date prévisionnelle croissante
     */
    public function findForProject(TenantId $tenant, string $projectId): array;
}
