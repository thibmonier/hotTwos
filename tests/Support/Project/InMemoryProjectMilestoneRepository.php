<?php

declare(strict_types=1);

namespace App\Tests\Support\Project;

use App\Domain\Project\ProjectMilestone;
use App\Domain\Project\ProjectMilestoneRepository;
use App\Domain\Tenant\TenantId;

final class InMemoryProjectMilestoneRepository implements ProjectMilestoneRepository
{
    /** @var list<ProjectMilestone> */
    public array $milestones = [];

    public function save(ProjectMilestone $milestone): void
    {
        foreach ($this->milestones as $existing) {
            if ($existing === $milestone) {
                return;
            }
        }
        $this->milestones[] = $milestone;
    }

    public function find(TenantId $tenant, string $milestoneId): ?ProjectMilestone
    {
        foreach ($this->milestones as $milestone) {
            if ($milestone->tenantId()->equals($tenant) && $milestone->id() === $milestoneId) {
                return $milestone;
            }
        }

        return null;
    }

    public function findForProject(TenantId $tenant, string $projectId): array
    {
        return array_values(array_filter(
            $this->milestones,
            static fn (ProjectMilestone $m): bool => $m->tenantId()->equals($tenant) && $m->projectId() === $projectId,
        ));
    }
}
