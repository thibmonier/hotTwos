<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Project;

use App\Application\Authorization\Authorizer;
use App\Application\Project\ManageExternalCommitments;
use App\Domain\Authorization\DataScope;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\Role;
use App\Domain\Project\CommitmentStatus;
use App\Domain\Project\CommitmentType;
use App\Domain\Project\ContractType;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectException;
use App\Domain\Project\ProjectStatus;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Tests\Support\Authorization\InMemoryRoleRepository;
use App\Tests\Support\Authorization\RecordingSecurityAuditLogger;
use App\Tests\Support\Project\InMemoryExternalCommitmentRepository;
use App\Tests\Support\Timesheet\InMemoryProjectRepository;
use PHPUnit\Framework\TestCase;

/**
 * US-034 (T-034-05) — engagements externes : validation (montant/fournisseur), refus sur projet
 * clôturé (CA-5), agrégation des coûts externes (marge).
 */
final class ExternalCommitmentTest extends TestCase
{
    private TenantId $tenant;
    private InMemoryProjectRepository $projects;
    private InMemoryExternalCommitmentRepository $commitments;
    private Authorizer $authorizer;
    private User $marc;
    private string $projectId;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $roles = new InMemoryRoleRepository();
        $roles->add(new Role($this->tenant, 'Chef de projet', [Permission::EDIT_PROJECT], DataScope::OWN_PROJECTS));
        $this->projects = new InMemoryProjectRepository();
        $this->commitments = new InMemoryExternalCommitmentRepository();
        $this->authorizer = new Authorizer($roles, new RecordingSecurityAuditLogger());
        $this->marc = new User($this->tenant, 'marc@agence.test', 'hash', ['Chef de projet']);

        $project = Project::createBusiness($this->tenant, 'PRJ-0042', 'Refonte', 'Acme', 'marc', 5_600_000, ContractType::FORFAIT, null, null);
        $this->projects->save($project);
        $this->projectId = $project->id();
    }

    public function testCreatesAndAggregatesExternalCosts(): void
    {
        $uc = $this->useCase();
        $uc->create($this->marc, $this->projectId, CommitmentType::SOUS_TRAITANCE, 'Maquettage', 450_000, 'DevShop', CommitmentStatus::ENGAGE, null);
        $uc->create($this->marc, $this->projectId, CommitmentType::ACHAT_LOGICIEL, 'Licence tests', 80_000, 'ToolCo', CommitmentStatus::PREVISIONNEL, null);

        self::assertSame(530_000, $uc->totalExternalCents($this->marc, $this->projectId));
    }

    public function testMissingSupplierIsRejected(): void
    {
        $this->expectException(ProjectException::class);
        $this->useCase()->create($this->marc, $this->projectId, CommitmentType::SOUS_TRAITANCE, 'X', 600_000, '  ', CommitmentStatus::ENGAGE, null);
    }

    public function testRejectedOnClosedProject(): void
    {
        // Amener le projet à « Clôturé » via les transitions autorisées.
        $project = $this->projects->find($this->tenant, $this->projectId);
        self::assertNotNull($project);
        $project->changeStatus(ProjectStatus::EN_COURS);
        $project->changeStatus(ProjectStatus::LIVRE_ATTENTE_RECEPTION);
        $project->changeStatus(ProjectStatus::RECEPTIONNE);
        $project->changeStatus(ProjectStatus::CLOTURE);

        $this->expectException(ProjectException::class);
        $this->useCase()->create($this->marc, $this->projectId, CommitmentType::AUTRE, 'X', 100_000, 'Fournisseur', CommitmentStatus::PREVISIONNEL, null);
    }

    private function useCase(): ManageExternalCommitments
    {
        return new ManageExternalCommitments($this->authorizer, $this->projects, $this->commitments, new RecordingSecurityAuditLogger());
    }
}
