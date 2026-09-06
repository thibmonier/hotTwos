<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Project;

use App\Application\Authorization\Authorizer;
use App\Application\Project\DefineLotProfileBudget;
use App\Domain\Authorization\AccessDeniedException;
use App\Domain\Authorization\DataScope;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\Role;
use App\Domain\Project\ContractType;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectException;
use App\Domain\Project\ProjectLot;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Tests\Support\Authorization\InMemoryRoleRepository;
use App\Tests\Support\Authorization\RecordingSecurityAuditLogger;
use App\Tests\Support\Project\InMemoryLotProfileBudgetRepository;
use App\Tests\Support\Project\InMemoryProjectLotRepository;
use App\Tests\Support\Timesheet\InMemoryProjectRepository;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * US-078 (EF-PRJ-9) — définition du budget par profil : habilitation EDIT_PROJECT, upsert par
 * couple (lot, profil).
 */
final class DefineLotProfileBudgetTest extends TestCase
{
    private const string SENIOR = '018f9c4e-0000-7000-8000-0000000000c1';

    private TenantId $tenant;
    private InMemoryProjectRepository $projects;
    private InMemoryProjectLotRepository $lots;
    private InMemoryLotProfileBudgetRepository $budgets;
    private Authorizer $authorizer;
    private User $marc;
    private User $observer;
    private string $lotId;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $roles = new InMemoryRoleRepository();
        $roles->add(new Role($this->tenant, 'Chef de projet', [Permission::EDIT_PROJECT], DataScope::OWN_PROJECTS));
        $roles->add(new Role($this->tenant, 'Observateur', [Permission::VIEW_PROJECT], DataScope::TENANT));

        $this->projects = new InMemoryProjectRepository();
        $this->lots = new InMemoryProjectLotRepository();
        $this->budgets = new InMemoryLotProfileBudgetRepository();
        $this->authorizer = new Authorizer($roles, new RecordingSecurityAuditLogger());
        $this->marc = new User($this->tenant, 'marc@agence.test', 'hash', ['Chef de projet']);
        $this->observer = new User($this->tenant, 'obs@agence.test', 'hash', ['Observateur']);

        $project = Project::createBusiness($this->tenant, 'PRJ-0042', 'Refonte', 'Acme', 'marc', 100_000_00, ContractType::FORFAIT, new DateTimeImmutable('2026-09-01'), new DateTimeImmutable('2027-03-31'));
        $this->projects->save($project);
        $lot = new ProjectLot($this->tenant, $project->id(), 'Développement', 60, 48_000_00);
        $this->lots->save($lot);
        $this->lotId = $lot->id();
    }

    public function testChefDefinesProfileBudget(): void
    {
        $this->useCase()->define($this->marc, $this->lotId, self::SENIOR, 40);

        $lines = $this->budgets->findForLot($this->tenant, $this->lotId);
        self::assertCount(1, $lines);
        self::assertSame(40, $lines[0]->days());
    }

    public function testRedefiningSameProfileUpdatesDays(): void
    {
        $this->useCase()->define($this->marc, $this->lotId, self::SENIOR, 40);
        $this->useCase()->define($this->marc, $this->lotId, self::SENIOR, 55);

        $lines = $this->budgets->findForLot($this->tenant, $this->lotId);
        self::assertCount(1, $lines);
        self::assertSame(55, $lines[0]->days());
    }

    public function testObserverIsDenied(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->useCase()->define($this->observer, $this->lotId, self::SENIOR, 40);
    }

    public function testUnknownLotIsRejected(): void
    {
        $this->expectException(ProjectException::class);
        $this->useCase()->define($this->marc, '018f9c4e-0000-7000-8000-0000000fffff', self::SENIOR, 40);
    }

    private function useCase(): DefineLotProfileBudget
    {
        return new DefineLotProfileBudget($this->authorizer, $this->projects, $this->lots, $this->budgets);
    }
}
