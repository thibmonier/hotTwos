<?php

declare(strict_types=1);

namespace App\Application\Project;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\SecurityAuditLogger;
use App\Domain\Project\ContractType;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectRepository;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use DateTimeImmutable;

/**
 * Création d'un projet métier (US-030, CA-1, RG-PRJ-1). Habilitation `CREATE_PROJECT` (403 sinon).
 * Génère un code séquentiel `PRJ-XXXX` par tenant, applique les obligations (client, responsable,
 * budget) via {@see Project::createBusiness()} (statut initial « En préparation »), et trace la création.
 */
final readonly class CreateProject
{
    public function __construct(
        private Authorizer $authorizer,
        private ProjectRepository $projects,
        private SecurityAuditLogger $audit,
    ) {
    }

    public function create(
        User $user,
        string $name,
        string $clientName,
        string $responsibleUserId,
        int $budgetCents,
        ContractType $contractType,
        ?DateTimeImmutable $startDate,
        ?DateTimeImmutable $endDate,
    ): Project {
        $this->authorizer->ensureCan($user, Permission::CREATE_PROJECT);
        $tenant = $user->tenantId();

        $project = Project::createBusiness(
            $tenant,
            $this->nextCode($tenant),
            $name,
            $clientName,
            $responsibleUserId,
            $budgetCents,
            $contractType,
            $startDate,
            $endDate,
        );
        $this->projects->save($project);

        $this->audit->record('project_created', $tenant->toString(), $user->getUserIdentifier(), [
            'project' => $project->code(),
        ]);

        return $project;
    }

    private function nextCode(TenantId $tenant): string
    {
        return sprintf('PRJ-%04d', $this->projects->countByTenant($tenant) + 1);
    }
}
