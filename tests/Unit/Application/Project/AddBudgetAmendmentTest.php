<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Project;

use App\Application\Authorization\Authorizer;
use App\Application\Project\AddBudgetAmendment;
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
use App\Tests\Support\Project\InMemoryBudgetAmendmentRepository;
use App\Tests\Support\Timesheet\InMemoryProjectRepository;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

/**
 * US-033 (EF-PRJ-8, RG-PRJ-4) — ajout d'avenant : habilitation EDIT_PROJECT, motif obligatoire,
 * refus sur projet clôturé, trace.
 */
final class AddBudgetAmendmentTest extends TestCase
{
    private TenantId $tenant;
    private InMemoryProjectRepository $projects;
    private InMemoryBudgetAmendmentRepository $amendments;
    private RecordingSecurityAuditLogger $audit;
    private Authorizer $authorizer;
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
        $this->amendments = new InMemoryBudgetAmendmentRepository();
        $this->audit = new RecordingSecurityAuditLogger();
        $this->authorizer = new Authorizer($roles, $this->audit);
        $this->marc = new User($this->tenant, 'marc@agence.test', 'hash', ['Chef de projet']);
        $this->observer = new User($this->tenant, 'obs@agence.test', 'hash', ['Observateur']);

        $project = Project::createBusiness($this->tenant, 'PRJ-0042', 'Refonte', 'Acme', 'marc', 100_000_00, ContractType::FORFAIT, new DateTimeImmutable('2026-09-01'), new DateTimeImmutable('2027-03-31'));
        $this->projects->save($project);
        $this->projectId = $project->id();
    }

    public function testChefAddsAmendment(): void
    {
        $this->useCase()->add($this->marc, $this->projectId, 20_000_00, 24_000_00, 'périmètre étendu');

        self::assertCount(1, $this->amendments->findForProject($this->tenant, $this->projectId));
        self::assertTrue($this->audit->has('project_budget_amended'));
    }

    public function testMotifIsRequired(): void
    {
        $this->expectException(ProjectException::class);
        $this->useCase()->add($this->marc, $this->projectId, 20_000_00, 0, '   ');
    }

    public function testObserverIsDenied(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->useCase()->add($this->observer, $this->projectId, 20_000_00, 0, 'périmètre étendu');
    }

    public function testAmendmentRefusedOnClosedProject(): void
    {
        $project = $this->projects->find($this->tenant, $this->projectId);
        self::assertNotNull($project);
        $project->changeStatus(ProjectStatus::EN_COURS);
        $project->close('marc', new DateTimeImmutable('2027-03-31'));
        $this->projects->save($project);

        $this->expectException(ProjectException::class);
        $this->useCase()->add($this->marc, $this->projectId, 20_000_00, 0, 'trop tard');
    }

    private function useCase(): AddBudgetAmendment
    {
        return new AddBudgetAmendment(
            $this->authorizer,
            $this->projects,
            $this->amendments,
            $this->audit,
            new MockClock(new DateTimeImmutable('2027-01-10 09:00:00', new DateTimeZone('UTC'))),
        );
    }
}
