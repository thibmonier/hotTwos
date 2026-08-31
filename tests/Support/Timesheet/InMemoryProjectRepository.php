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

    public function find(TenantId $tenant, string $projectId): ?Project
    {
        foreach ($this->projects as $project) {
            if ($project->tenantId()->equals($tenant) && $project->id() === $projectId) {
                return $project;
            }
        }

        return null;
    }

    public function findAllActive(TenantId $tenant): array
    {
        $active = array_values(array_filter(
            $this->projects,
            static fn (Project $project): bool => $project->tenantId()->equals($tenant) && $project->isActive(),
        ));
        usort($active, static fn (Project $a, Project $b): int => $a->code() <=> $b->code());

        return $active;
    }

    public function findByResponsible(TenantId $tenant, string $responsibleUserId): array
    {
        $found = array_values(array_filter(
            $this->projects,
            static fn (Project $project): bool => $project->tenantId()->equals($tenant) && $project->isResponsible($responsibleUserId),
        ));
        usort($found, static fn (Project $a, Project $b): int => $a->code() <=> $b->code());

        return $found;
    }

    public function save(Project $project): void
    {
        $this->projects[] = $project;
    }
}
