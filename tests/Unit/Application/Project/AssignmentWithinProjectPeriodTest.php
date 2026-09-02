<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Project;

use App\Application\Authorization\Authorizer;
use App\Application\Project\ManageAssignments;
use App\Domain\Authorization\DataScope;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\Role;
use App\Domain\Project\ContractType;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectAssignment;
use App\Domain\Project\ProjectException;
use App\Domain\Project\ProjectStatus;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Tests\Support\Authorization\InMemoryRoleRepository;
use App\Tests\Support\Authorization\RecordingSecurityAuditLogger;
use App\Tests\Support\Project\InMemoryExceptionalImputationOpeningRepository;
use App\Tests\Support\Project\InMemoryProjectAssignmentRepository;
use App\Tests\Support\Timesheet\InMemoryProjectRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use DateTimeImmutable;

/**
 * Régression REG-S6-002 (recette Sprint 6, finding F2) — une affectation ne peut pas déborder la
 * date de fin contractuelle du projet (US-037/CA-6, EF-PRJ-20). Une affectation strictement dans
 * la période reste acceptée.
 *
 * @group regression
 */
final class AssignmentWithinProjectPeriodTest extends TestCase
{
    private ManageAssignments $uc;
    private InMemoryProjectAssignmentRepository $assignments;
    private User $marc;
    private string $projectId;

    protected function setUp(): void
    {
        $tenant = TenantId::generate();
        $roles = new InMemoryRoleRepository();
        $roles->add(new Role($tenant, 'Chef de projet', [Permission::EDIT_PROJECT], DataScope::OWN_PROJECTS));
        $authorizer = new Authorizer($roles, new RecordingSecurityAuditLogger());
        $this->marc = new User($tenant, 'marc@agence.test', 'hash', ['Chef de projet']);

        $projects = new InMemoryProjectRepository();
        $project = Project::createBusiness(
            $tenant,
            'PRJ-0042',
            'Refonte',
            'Acme',
            'marc',
            20_000_000,
            ContractType::FORFAIT,
            new DateTimeImmutable('2026-10-01'),
            new DateTimeImmutable('2027-01-31'),
        );
        $project->changeStatus(ProjectStatus::EN_COURS);
        $projects->save($project);
        $this->projectId = $project->id();

        $this->assignments = new InMemoryProjectAssignmentRepository();
        $this->uc = new ManageAssignments(
            $authorizer,
            $projects,
            $this->assignments,
            new InMemoryExceptionalImputationOpeningRepository(),
            new RecordingSecurityAuditLogger(),
            new MockClock(new DateTimeImmutable('2026-09-02')),
        );
    }

    public function testAssignmentEndingAfterProjectEndIsRefused(): void
    {
        $this->expectException(ProjectException::class);
        $this->uc->assign($this->marc, $this->projectId, 'julie', 'Développeuse', 60, new DateTimeImmutable('2026-10-01'), new DateTimeImmutable('2027-06-30'));
    }

    public function testAssignmentWithinProjectPeriodIsAccepted(): void
    {
        $assignment = $this->uc->assign($this->marc, $this->projectId, 'julie', 'Développeuse', 60, new DateTimeImmutable('2026-10-01'), new DateTimeImmutable('2027-01-31'));

        self::assertInstanceOf(ProjectAssignment::class, $assignment);
        self::assertCount(1, $this->assignments->findForProject($this->marc->tenantId(), $this->projectId));
    }
}
