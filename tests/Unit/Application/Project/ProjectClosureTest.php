<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Project;

use App\Application\Authorization\Authorizer;
use App\Application\Project\ManageProjectClosure;
use App\Domain\Authorization\DataScope;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\Role;
use App\Domain\Project\ContractType;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectException;
use App\Domain\Project\ProjectReopening;
use App\Domain\Tenant\TenantId;
use App\Domain\Timesheet\TimeEntry;
use App\Domain\User\User;
use App\Tests\Support\Authorization\InMemoryRoleRepository;
use App\Tests\Support\Authorization\RecordingSecurityAuditLogger;
use App\Tests\Support\Project\InMemoryExternalCommitmentRepository;
use App\Tests\Support\Project\InMemoryProjectMilestoneRepository;
use App\Tests\Support\Project\InMemoryProjectReopeningRepository;
use App\Tests\Support\Timesheet\InMemoryProjectRepository;
use App\Tests\Support\Timesheet\InMemoryTimeEntryRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use DateTimeImmutable;
use DateTimeZone;

/**
 * US-038 (T-038-06) — clôture : blocage sur imputations non validées (CA-6), avertissement
 * jalons/engagements (CA-4), réouverture 4-eyes (CA-3).
 */
final class ProjectClosureTest extends TestCase
{
    private TenantId $tenant;
    private InMemoryProjectRepository $projects;
    private InMemoryTimeEntryRepository $entries;
    private InMemoryProjectMilestoneRepository $milestones;
    private InMemoryExternalCommitmentRepository $commitments;
    private InMemoryProjectReopeningRepository $reopenings;
    private Authorizer $authorizer;
    private User $marc;
    private User $admin;
    private string $projectId;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $roles = new InMemoryRoleRepository();
        $roles->add(new Role($this->tenant, 'Chef de projet', [Permission::EDIT_PROJECT], DataScope::OWN_PROJECTS));
        $roles->add(new Role($this->tenant, 'Administrateur', [Permission::EDIT_PROJECT, Permission::MANAGE_ORGANIZATION], DataScope::TENANT));

        $this->projects = new InMemoryProjectRepository();
        $this->entries = new InMemoryTimeEntryRepository();
        $this->milestones = new InMemoryProjectMilestoneRepository();
        $this->commitments = new InMemoryExternalCommitmentRepository();
        $this->reopenings = new InMemoryProjectReopeningRepository();
        $this->authorizer = new Authorizer($roles, new RecordingSecurityAuditLogger());
        $this->marc = new User($this->tenant, 'marc@agence.test', 'hash', ['Chef de projet']);
        $this->admin = new User($this->tenant, 'admin@agence.test', 'hash', ['Administrateur']);

        $project = Project::createBusiness($this->tenant, 'PRJ-0042', 'Refonte', 'Acme', 'marc', 5_000_000, ContractType::FORFAIT, null, null);
        $this->projects->save($project);
        $this->projectId = $project->id();
    }

    public function testCloseBlockedByUnvalidatedEntries(): void
    {
        $this->entries->save(new TimeEntry($this->tenant, 'camille', $this->projectId, new DateTimeImmutable('2026-09-15'), 240)); // PENDING

        $this->expectException(ProjectException::class);
        $this->closure()->close($this->marc, $this->projectId, true);
    }

    public function testCloseSucceedsWhenClean(): void
    {
        $this->closure()->close($this->marc, $this->projectId, false);

        self::assertTrue($this->projects->find($this->tenant, $this->projectId)?->isClosed());
    }

    public function testReopeningRequiresFourEyes(): void
    {
        $this->closure()->close($this->admin, $this->projectId, false);
        // L'admin demande ET tente d'approuver sa propre demande → refus 4-eyes (personne distincte).
        $reopening = $this->closure()->requestReopening($this->admin, $this->projectId, 'Régularisation');

        $this->expectException(ProjectException::class);
        $this->closure()->approveReopening($this->admin, $this->projectId, $reopening->id(), new DateTimeImmutable('2026-12-31', new DateTimeZone('UTC')));
    }

    public function testAdminApprovesReopeningWindow(): void
    {
        $this->closure()->close($this->marc, $this->projectId, false);
        $reopening = $this->closure()->requestReopening($this->marc, $this->projectId, 'Régularisation');

        $this->closure()->approveReopening($this->admin, $this->projectId, $reopening->id(), new DateTimeImmutable('2026-12-31', new DateTimeZone('UTC')));

        $stored = $this->reopenings->find($this->tenant, $reopening->id());
        self::assertInstanceOf(ProjectReopening::class, $stored);
        self::assertTrue($stored->isApproved());
        self::assertTrue($stored->isActiveOn(new DateTimeImmutable('2026-12-01', new DateTimeZone('UTC'))));
    }

    private function closure(): ManageProjectClosure
    {
        return new ManageProjectClosure(
            $this->authorizer,
            $this->projects,
            $this->entries,
            $this->milestones,
            $this->commitments,
            $this->reopenings,
            new RecordingSecurityAuditLogger(),
            new MockClock(new DateTimeImmutable('2026-11-01 09:00:00', new DateTimeZone('UTC'))),
        );
    }
}
