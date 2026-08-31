<?php

declare(strict_types=1);

namespace App\Tests\Support\Timesheet;

use App\Domain\Project\Project;
use App\Domain\Project\ProjectRepository;
use App\Domain\Tenant\TenantId;

final class InMemoryProjectRepository implements ProjectRepository
{
    /** @var list<Project> */
    private array $projects = [];

    public function findActive(TenantId $tenant, string $projectId): ?Project
    {
        foreach ($this->projects as $project) {
            if ($project->tenantId()->equals($tenant) && $project->id() === $projectId && $project->isActive()) {
                return $project;
            }
        }

        return null;
    }

    public function save(Project $project): void
    {
        $this->projects[] = $project;
    }
}
