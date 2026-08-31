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

    /**
     * Charge un projet du tenant quel que soit son état (actif ou non).
     */
    public function find(TenantId $tenant, string $projectId): ?Project;

    /**
     * @return list<Project> projets actifs du tenant, triés par code
     */
    public function findAllActive(TenantId $tenant): array;

    /**
     * @return list<Project> projets du tenant dont l'utilisateur est responsable (US-055)
     */
    public function findByResponsible(TenantId $tenant, string $responsibleUserId): array;

    public function save(Project $project): void;
}
