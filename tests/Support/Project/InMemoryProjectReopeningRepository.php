<?php

declare(strict_types=1);

namespace App\Tests\Support\Project;

use App\Domain\Project\ProjectReopening;
use App\Domain\Project\ProjectReopeningRepository;
use App\Domain\Tenant\TenantId;
use DateTimeImmutable;

final class InMemoryProjectReopeningRepository implements ProjectReopeningRepository
{
    /** @var list<ProjectReopening> */
    public array $reopenings = [];

    public function save(ProjectReopening $reopening): void
    {
        foreach ($this->reopenings as $existing) {
            if ($existing === $reopening) {
                return;
            }
        }
        $this->reopenings[] = $reopening;
    }

    public function find(TenantId $tenant, string $reopeningId): ?ProjectReopening
    {
        foreach ($this->reopenings as $reopening) {
            if ($reopening->tenantId()->equals($tenant) && $reopening->id() === $reopeningId) {
                return $reopening;
            }
        }

        return null;
    }

    public function findForProject(TenantId $tenant, string $projectId): array
    {
        return array_values(array_filter(
            $this->reopenings,
            static fn (ProjectReopening $r): bool => $r->tenantId()->equals($tenant) && $r->projectId() === $projectId,
        ));
    }

    public function hasActiveOn(TenantId $tenant, string $projectId, DateTimeImmutable $day): bool
    {
        return array_any($this->reopenings, fn (ProjectReopening $r): bool => $r->tenantId()->equals($tenant) && $r->projectId() === $projectId && $r->isActiveOn($day));
    }
}
