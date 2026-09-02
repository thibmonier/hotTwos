<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Project;

use App\Application\Authorization\Authorizer;
use App\Application\Project\ChangeProjectStatus;
use App\Application\Project\CreateProject;
use App\Domain\Authorization\AccessDeniedException;
use App\Domain\Authorization\DataScope;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\Role;
use App\Domain\Project\ContractType;
use App\Domain\Project\ProjectException;
use App\Domain\Project\ProjectStatus;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Tests\Support\Authorization\InMemoryRoleRepository;
use App\Tests\Support\Authorization\RecordingSecurityAuditLogger;
use App\Tests\Support\Timesheet\InMemoryProjectRepository;
use PHPUnit\Framework\TestCase;

/**
 * US-030 (T-030-03/04) — création et cycle de vie via les cas d'usage : habilitation (403), code
 * séquentiel PRJ-XXXX, RG-PRJ-1, transitions de statut, traçabilité.
 */
final class ProjectLifecycleTest extends TestCase
{
    private TenantId $tenant;
    private InMemoryProjectRepository $projects;
    private RecordingSecurityAuditLogger $audit;
    private Authorizer $authorizer;
    private User $marc;
    private User $camille;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $roles = new InMemoryRoleRepository();
        $roles->add(new Role($this->tenant, 'Chef de projet', [Permission::CREATE_PROJECT, Permission::EDIT_PROJECT], DataScope::OWN_PROJECTS));
        $roles->add(new Role($this->tenant, 'Collaborateur', [Permission::VIEW_PROJECT], DataScope::OWN));

        $this->projects = new InMemoryProjectRepository();
        $this->audit = new RecordingSecurityAuditLogger();
        $this->authorizer = new Authorizer($roles, $this->audit);
        $this->marc = new User($this->tenant, 'marc@agence.test', 'hash', ['Chef de projet']);
        $this->camille = new User($this->tenant, 'camille@agence.test', 'hash', ['Collaborateur']);
    }

    public function testCreatesProjectWithSequentialCodeAndInitialStatus(): void
    {
        $project = $this->creator()->create($this->marc, 'Refonte SI', 'Acme Corp', 'marc-id', 12_000_000, ContractType::FORFAIT, null, null);

        self::assertSame('PRJ-0001', $project->code());
        self::assertSame(ProjectStatus::EN_PREPARATION, $project->status());
        self::assertTrue($this->audit->has('project_created'));

        $second = $this->creator()->create($this->marc, 'Autre', 'Beta', 'marc-id', 5_000_000, ContractType::REGIE, null, null);
        self::assertSame('PRJ-0002', $second->code());
    }

    public function testCollaboratorCannotCreate(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->creator()->create($this->camille, 'Refonte', 'Acme', 'x', 1_000_000, ContractType::FORFAIT, null, null);
    }

    public function testCreationRejectedWithoutBudget(): void
    {
        $this->expectException(ProjectException::class);
        $this->creator()->create($this->marc, 'Refonte', 'Acme', 'marc-id', 0, ContractType::FORFAIT, null, null);
    }

    public function testStatusTransitionAndInvalidTransition(): void
    {
        $project = $this->creator()->create($this->marc, 'Refonte SI', 'Acme Corp', 'marc-id', 12_000_000, ContractType::FORFAIT, null, null);

        $this->changer()->change($this->marc, $project->id(), ProjectStatus::EN_COURS);
        self::assertSame(ProjectStatus::EN_COURS, $this->projects->find($this->tenant, $project->id())?->status());

        $this->expectException(ProjectException::class);
        $this->changer()->change($this->marc, $project->id(), ProjectStatus::CLOTURE);
    }

    private function creator(): CreateProject
    {
        return new CreateProject($this->authorizer, $this->projects, $this->audit);
    }

    private function changer(): ChangeProjectStatus
    {
        return new ChangeProjectStatus($this->authorizer, $this->projects, $this->audit);
    }
}
