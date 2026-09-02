<?php

declare(strict_types=1);

namespace App\Domain\Project;

use App\Domain\Tenant\TenantId;

/**
 * Port de persistance des lots de projet (US-031, DIP). Tenant explicite.
 */
interface ProjectLotRepository
{
    public function save(ProjectLot $lot): void;

    public function find(TenantId $tenant, string $lotId): ?ProjectLot;

    /**
     * @return list<ProjectLot> lots du projet (tous niveaux)
     */
    public function findForProject(TenantId $tenant, string $projectId): array;
}
