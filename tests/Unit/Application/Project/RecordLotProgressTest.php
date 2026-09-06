<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Project;

use App\Application\Authorization\Authorizer;
use App\Application\Project\ManageProjectLots;
use App\Domain\Authorization\AccessDeniedException;
use App\Domain\Authorization\DataScope;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\Role;
use App\Domain\Project\ContractType;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectException;
use App\Domain\Project\ProjectStatus;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Tests\Support\Authorization\InMemoryRoleRepository;
use App\Tests\Support\Authorization\RecordingSecurityAuditLogger;
use App\Tests\Support\Project\InMemoryProjectLotRepository;
use App\Tests\Support\Timesheet\InMemoryProjectRepository;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * US-035 (CA-1/CA-3/CA-6) — saisie de l'avancement/RAF d'un lot via {@see ManageProjectLots}::recordProgress :
 * habilitation EDIT_PROJECT, refus sur projet clôturé (RG-PRJ-5), lot introuvable.
 */
final class RecordLotProgressTest extends TestCase
{
    private TenantId $tenant;
    private InMemoryProjectRepository $projects;
    private InMemoryProjectLotRepository $lots;
    private Authorizer $authorizer;
    private RecordingSecurityAuditLogger $audit;
    private User $marc;
    private User $observer;
    private string $projectId;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $roles = new InMemoryRoleRepository();
        $roles->add(new Role($this->tenant, 'Chef de projet', [Permission::EDIT_PROJECT], DataScope::OWN_PROJECTS));
        $roles->add(new Role($this->tenant, 'Observateur', [Permission::VIEW_PROJECT], DataScope::TENANT));

        $this->projects = new InMemoryProjectRepository();
        $this->lots = new InMemoryProjectLotRepository();
        $this->audit = new RecordingSecurityAuditLogger();
        $this->authorizer = new Authorizer($roles, $this->audit);
        $this->marc = new User($this->tenant, 'marc@agence.test', 'hash', ['Chef de projet']);
        $this->observer = new User($this->tenant, 'obs@agence.test', 'hash', ['Observateur']);

        $project = Project::createBusiness($this->tenant, 'PRJ-0042', 'Refonte', 'Acme', 'marc', 20_000_000, ContractType::FORFAIT, new DateTimeImmutable('2026-09-01'), new DateTimeImmutable('2027-03-31'));
        $this->projects->save($project);
        $this->projectId = $project->id();
    }

    public function testChefRecordsProgress(): void
    {
        $lot = $this->useCase()->addLot($this->marc, $this->projectId, 'Développement', 120, 9_600_000, null, false);

        $this->useCase()->recordProgress($this->marc, $lot->id(), 40, 6);

        $stored = $this->lots->find($this->tenant, $lot->id());
        self::assertNotNull($stored);
        self::assertSame(40, $stored->physicalProgressPercent());
        self::assertSame(6, $stored->remainingWorkDays());
        self::assertTrue($this->audit->has('project_lot_progress_recorded'));
    }

    public function testObserverWithoutEditIsDenied(): void
    {
        $lot = $this->useCase()->addLot($this->marc, $this->projectId, 'Développement', 120, 9_600_000, null, false);

        $this->expectException(AccessDeniedException::class);
        $this->useCase()->recordProgress($this->observer, $lot->id(), 40, 6);
    }

    public function testUnknownLotIsRejected(): void
    {
        $this->expectException(ProjectException::class);
        $this->useCase()->recordProgress($this->marc, '018f9c4e-0000-7000-8000-0000000fffff', 40, 6);
    }

    public function testProgressRefusedOnClosedProject(): void
    {
        $lot = $this->useCase()->addLot($this->marc, $this->projectId, 'Développement', 120, 9_600_000, null, false);

        $project = $this->projects->find($this->tenant, $this->projectId);
        self::assertNotNull($project);
        $project->changeStatus(ProjectStatus::EN_COURS);
        $project->close('marc', new DateTimeImmutable('2027-03-31'));
        $this->projects->save($project);

        $this->expectException(ProjectException::class);
        $this->useCase()->recordProgress($this->marc, $lot->id(), 50, 4);
    }

    private function useCase(): ManageProjectLots
    {
        return new ManageProjectLots($this->authorizer, $this->projects, $this->lots, $this->audit);
    }
}
