<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Project;

use App\Application\Authorization\Authorizer;
use App\Application\Project\ManageAssignments;
use App\Application\Project\ManageMilestones;
use App\Application\Project\ManageProjectLots;
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
use App\Tests\Support\Project\InMemoryExceptionalImputationOpeningRepository;
use App\Tests\Support\Project\InMemoryProjectAssignmentRepository;
use App\Tests\Support\Project\InMemoryProjectLotRepository;
use App\Tests\Support\Project\InMemoryProjectMilestoneRepository;
use App\Tests\Support\Timesheet\InMemoryProjectRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use DateTimeImmutable;

/**
 * Régression REG-S6-001 (recette Sprint 6, finding F3) — un projet **clôturé** doit être en
 * lecture seule (US-038/CA-2, RG-PRJ-5) : ni lot, ni jalon, ni affectation, ni ouverture
 * exceptionnelle ne peut y être ajouté après clôture. La réouverture (4-eyes) ne rouvre que
 * l'imputation de temps, pas l'édition structurelle.
 *
 * @group regression
 */
final class ClosedProjectReadOnlyTest extends TestCase
{
    private TenantId $tenant;
    private InMemoryProjectRepository $projects;
    private Authorizer $authorizer;
    private User $marc;
    private string $projectId;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $roles = new InMemoryRoleRepository();
        $roles->add(new Role($this->tenant, 'Chef de projet', [Permission::EDIT_PROJECT], DataScope::OWN_PROJECTS));

        $this->projects = new InMemoryProjectRepository();
        $this->authorizer = new Authorizer($roles, new RecordingSecurityAuditLogger());
        $this->marc = new User($this->tenant, 'marc@agence.test', 'hash', ['Chef de projet']);

        $project = Project::createBusiness(
            $this->tenant,
            'PRJ-0042',
            'Refonte',
            'Acme',
            'marc',
            20_000_000,
            ContractType::FORFAIT,
            new DateTimeImmutable('2026-09-01'),
            new DateTimeImmutable('2027-03-31'),
        );
        $project->changeStatus(ProjectStatus::EN_COURS);
        $project->close('marc', new DateTimeImmutable('2027-03-31'));
        $this->projects->save($project);
        $this->projectId = $project->id();
    }

    public function testAddLotRefusedOnClosedProject(): void
    {
        $uc = new ManageProjectLots($this->authorizer, $this->projects, new InMemoryProjectLotRepository(), new RecordingSecurityAuditLogger());

        $this->expectException(ProjectException::class);
        $uc->addLot($this->marc, $this->projectId, 'Lot post-clôture', 5, 10_000_00, null, true);
    }

    public function testAddMilestoneRefusedOnClosedProject(): void
    {
        $uc = new ManageMilestones($this->authorizer, $this->projects, new InMemoryProjectMilestoneRepository(), new RecordingSecurityAuditLogger());

        $this->expectException(ProjectException::class);
        $uc->addMilestone($this->marc, $this->projectId, 'Jalon post-clôture', new DateTimeImmutable('2026-11-01'), null);
    }

    public function testAssignRefusedOnClosedProject(): void
    {
        $this->expectException(ProjectException::class);
        $this->assignments()->assign($this->marc, $this->projectId, 'julie', 'Dev', 10, null, null);
    }

    public function testGrantOpeningRefusedOnClosedProject(): void
    {
        $this->expectException(ProjectException::class);
        $this->assignments()->grantOpening($this->marc, $this->projectId, 'julie', new DateTimeImmutable('2026-11-02'), 'post-clôture');
    }

    private function assignments(): ManageAssignments
    {
        return new ManageAssignments(
            $this->authorizer,
            $this->projects,
            new InMemoryProjectAssignmentRepository(),
            new InMemoryExceptionalImputationOpeningRepository(),
            new RecordingSecurityAuditLogger(),
            new MockClock(new DateTimeImmutable('2026-09-02')),
        );
    }
}
