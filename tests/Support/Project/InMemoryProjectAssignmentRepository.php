<?php

declare(strict_types=1);

namespace App\Tests\Support\Project;

use App\Domain\Project\ProjectAssignment;
use App\Domain\Project\ProjectAssignmentRepository;
use App\Domain\Tenant\TenantId;
use DateTimeImmutable;

final class InMemoryProjectAssignmentRepository implements ProjectAssignmentRepository
{
    /** @var list<ProjectAssignment> */
    public array $assignments = [];

    public function save(ProjectAssignment $assignment): void
    {
        foreach ($this->assignments as $existing) {
            if ($existing === $assignment) {
                return;
            }
        }
        $this->assignments[] = $assignment;
    }

    public function find(TenantId $tenant, string $assignmentId): ?ProjectAssignment
    {
        foreach ($this->assignments as $assignment) {
            if ($assignment->tenantId()->equals($tenant) && $assignment->id() === $assignmentId) {
                return $assignment;
            }
        }

        return null;
    }

    public function remove(ProjectAssignment $assignment): void
    {
        $this->assignments = array_values(array_filter($this->assignments, static fn (ProjectAssignment $a): bool => $a !== $assignment));
    }

    public function findForProject(TenantId $tenant, string $projectId): array
    {
        return array_values(array_filter(
            $this->assignments,
            static fn (ProjectAssignment $a): bool => $a->tenantId()->equals($tenant) && $a->projectId() === $projectId,
        ));
    }

    public function hasAssignments(TenantId $tenant, string $projectId): bool
    {
        return [] !== $this->findForProject($tenant, $projectId);
    }

    public function isAssignedOn(TenantId $tenant, string $projectId, string $userId, DateTimeImmutable $day): bool
    {
        return array_any($this->assignments, fn (ProjectAssignment $a): bool => $a->tenantId()->equals($tenant) && $a->projectId() === $projectId && $a->userId() === $userId && $a->coversDay($day));
    }

    public function assignedProjectIds(TenantId $tenant, string $userId): array
    {
        $ids = [];
        foreach ($this->assignments as $a) {
            if ($a->tenantId()->equals($tenant) && $a->userId() === $userId) {
                $ids[$a->projectId()] = true;
            }
        }

        return array_keys($ids);
    }
}
