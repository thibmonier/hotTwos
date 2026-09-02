<?php

declare(strict_types=1);

namespace App\Domain\Project;

use App\Domain\Tenant\TenantId;
use DateTimeImmutable;

/**
 * Port de persistance des réouvertures de projet (US-038, DIP). Tenant explicite.
 */
interface ProjectReopeningRepository
{
    public function save(ProjectReopening $reopening): void;

    public function find(TenantId $tenant, string $reopeningId): ?ProjectReopening;

    /**
     * @return list<ProjectReopening> réouvertures du projet, plus récentes d'abord
     */
    public function findForProject(TenantId $tenant, string $projectId): array;

    /** Une réouverture approuvée couvre-t-elle ce jour pour ce projet ? (fenêtre active — CA-3/CA-7) */
    public function hasActiveOn(TenantId $tenant, string $projectId, DateTimeImmutable $day): bool;
}
