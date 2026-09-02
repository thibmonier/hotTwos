<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Project;

use App\Application\Authorization\Authorizer;
use App\Application\Project\ManageMilestones;
use App\Application\Project\ManageProjectLots;
use App\Domain\Authorization\DataScope;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\Role;
use App\Domain\Project\ContractType;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectException;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Tests\Support\Authorization\InMemoryRoleRepository;
use App\Tests\Support\Authorization\RecordingSecurityAuditLogger;
use App\Tests\Support\Project\InMemoryProjectLotRepository;
use App\Tests\Support\Project\InMemoryProjectMilestoneRepository;
use App\Tests\Support\Timesheet\InMemoryProjectRepository;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;
use DateTimeZone;

/**
 * US-031 (T-031-06) — lots (arbre 2 niveaux, dépassement budget confirmé, réallocation tracée) et
 * jalons (date dans la période, facturation idempotente).
 */
final class ProjectStructureTest extends TestCase
{
    private TenantId $tenant;
    private InMemoryProjectRepository $projects;
    private InMemoryProjectLotRepository $lotRepo;
    private InMemoryProjectMilestoneRepository $milestoneRepo;
    private Authorizer $authorizer;
    private RecordingSecurityAuditLogger $audit;
    private User $marc;
    private string $projectId;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $roles = new InMemoryRoleRepository();
        $roles->add(new Role($this->tenant, 'Chef de projet', [Permission::EDIT_PROJECT], DataScope::OWN_PROJECTS));
        $this->projects = new InMemoryProjectRepository();
        $this->lotRepo = new InMemoryProjectLotRepository();
        $this->milestoneRepo = new InMemoryProjectMilestoneRepository();
        $this->audit = new RecordingSecurityAuditLogger();
        $this->authorizer = new Authorizer($roles, $this->audit);
        $this->marc = new User($this->tenant, 'marc@agence.test', 'hash', ['Chef de projet']);

        $project = Project::createBusiness($this->tenant, 'PRJ-0042', 'Refonte', 'Acme', 'marc', 20_000_000, ContractType::FORFAIT, $this->d('2026-09-01'), $this->d('2027-03-31'));
        $this->projects->save($project);
        $this->projectId = $project->id();
    }

    public function testTwoLevelTreeAndSubLotCannotBeParent(): void
    {
        $lots = $this->lotsUseCase();
        $l2 = $lots->addLot($this->marc, $this->projectId, 'Développement', 120, 9_600_000, null, false);
        $sub = $lots->addLot($this->marc, $this->projectId, 'Module Auth', 40, 3_200_000, $l2->id(), false);

        $this->expectException(ProjectException::class);
        $lots->addLot($this->marc, $this->projectId, 'Trop profond', 10, 100_000, $sub->id(), false);
    }

    public function testRootOverrunRequiresConfirmation(): void
    {
        $lots = $this->lotsUseCase();
        // Budget projet 200 000 € ; un lot racine de 250 000 € dépasse → confirmation requise.
        try {
            $lots->addLot($this->marc, $this->projectId, 'Gros lot', 300, 25_000_000, null, false);
            self::fail('Le dépassement doit exiger une confirmation.');
        } catch (ProjectException) {
            // attendu
        }
        $lot = $lots->addLot($this->marc, $this->projectId, 'Gros lot', 300, 25_000_000, null, true);
        self::assertSame('Gros lot', $lot->name());
    }

    public function testReallocationRequiresReason(): void
    {
        $lots = $this->lotsUseCase();
        $lot = $lots->addLot($this->marc, $this->projectId, 'Analyse', 40, 3_200_000, null, false);

        $this->expectException(ProjectException::class);
        $lots->reallocate($this->marc, $lot->id(), 50, 4_000_000, '   ');
    }

    public function testMilestoneOutsidePeriodIsRejected(): void
    {
        $this->expectException(ProjectException::class);
        $this->milestonesUseCase()->addMilestone($this->marc, $this->projectId, 'Livraison', $this->d('2027-06-15'), null);
    }

    public function testMilestoneBillingIsIdempotent(): void
    {
        $ms = $this->milestonesUseCase();
        $milestone = $ms->addMilestone($this->marc, $this->projectId, 'Recette validée', $this->d('2027-02-28'), 6_000_000);
        $ms->markReached($this->marc, $milestone->id(), $this->at('2027-02-25 10:00:00'));

        $this->expectException(ProjectException::class);
        $ms->markReached($this->marc, $milestone->id(), $this->at('2027-03-01 10:00:00'));
    }

    private function lotsUseCase(): ManageProjectLots
    {
        return new ManageProjectLots($this->authorizer, $this->projects, $this->lotRepo, $this->audit);
    }

    private function milestonesUseCase(): ManageMilestones
    {
        return new ManageMilestones($this->authorizer, $this->projects, $this->milestoneRepo, $this->audit);
    }

    private function d(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value.' 00:00:00', new DateTimeZone('UTC'));
    }

    private function at(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value, new DateTimeZone('UTC'));
    }
}
