<?php

declare(strict_types=1);

namespace App\Domain\Project;

use App\Domain\Tenant\TenantId;

/**
 * Port de lecture des projets (US-050, DIP). Implémentation Doctrine en infrastructure.
 * Le cloisonnement par tenant est exprimé explicitement (le repo reçoit le TenantId).
 */
interface ProjectRepository
{
    public function findActive(TenantId $tenant, string $projectId): ?Project;

    public function save(Project $project): void;
}
