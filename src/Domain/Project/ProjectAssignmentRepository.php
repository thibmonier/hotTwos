<?php

declare(strict_types=1);

namespace App\Domain\Project;

use App\Domain\Tenant\TenantId;
use DateTimeImmutable;

/**
 * Port de persistance des affectations de projet (US-037, DIP). Tenant explicite.
 */
interface ProjectAssignmentRepository
{
    public function save(ProjectAssignment $assignment): void;

    public function find(TenantId $tenant, string $assignmentId): ?ProjectAssignment;

    public function remove(ProjectAssignment $assignment): void;

    /**
     * @return list<ProjectAssignment>
     */
    public function findForProject(TenantId $tenant, string $projectId): array;

    /** Le projet a-t-il au moins une affectation ? (au-delà : le projet devient restreint — CA-1) */
    public function hasAssignments(TenantId $tenant, string $projectId): bool;

    /** Le collaborateur est-il affecté au projet et couvrant ce jour (période d'affectation) ? */
    public function isAssignedOn(TenantId $tenant, string $projectId, string $userId, DateTimeImmutable $day): bool;

    /**
     * Identifiants des projets sur lesquels le collaborateur est affecté (filtrage de la saisie).
     *
     * @return list<string>
     */
    public function assignedProjectIds(TenantId $tenant, string $userId): array;
}
