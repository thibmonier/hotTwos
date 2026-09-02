<?php

declare(strict_types=1);

namespace App\Tests\Support\Project;

use App\Domain\Project\ProjectLot;
use App\Domain\Project\ProjectLotRepository;
use App\Domain\Tenant\TenantId;

final class InMemoryProjectLotRepository implements ProjectLotRepository
{
    /** @var list<ProjectLot> */
    public array $lots = [];

    public function save(ProjectLot $lot): void
    {
        foreach ($this->lots as $existing) {
            if ($existing === $lot) {
                return;
            }
        }
        $this->lots[] = $lot;
    }

    public function find(TenantId $tenant, string $lotId): ?ProjectLot
    {
        foreach ($this->lots as $lot) {
            if ($lot->tenantId()->equals($tenant) && $lot->id() === $lotId) {
                return $lot;
            }
        }

        return null;
    }

    public function findForProject(TenantId $tenant, string $projectId): array
    {
        return array_values(array_filter(
            $this->lots,
            static fn (ProjectLot $lot): bool => $lot->tenantId()->equals($tenant) && $lot->projectId() === $projectId,
        ));
    }
}
