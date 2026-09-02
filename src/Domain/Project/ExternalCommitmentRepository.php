<?php

declare(strict_types=1);

namespace App\Domain\Project;

use App\Domain\Tenant\TenantId;

/**
 * Port de persistance des engagements externes (US-034, DIP). Tenant explicite.
 */
interface ExternalCommitmentRepository
{
    public function save(ExternalCommitment $commitment): void;

    /**
     * @return list<ExternalCommitment> engagements du projet
     */
    public function findForProject(TenantId $tenant, string $projectId): array;
}
