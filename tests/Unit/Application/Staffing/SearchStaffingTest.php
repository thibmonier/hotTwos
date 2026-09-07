<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Staffing;

use App\Application\Authorization\Authorizer;
use App\Application\Staffing\SearchStaffing;
use App\Application\Staffing\ViewWorkloadPlan;
use App\Domain\Authorization\AccessDeniedException;
use App\Domain\Authorization\DataScope;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\Role;
use App\Domain\Calendar\WorkingDaysCalculator;
use App\Domain\Skill\SkillAssignment;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Tests\Support\Absence\InMemoryAbsenceRequestRepository;
use App\Tests\Support\Authorization\InMemoryRoleRepository;
use App\Tests\Support\Authorization\RecordingSecurityAuditLogger;
use App\Tests\Support\Calendar\InMemoryClosurePeriodRepository;
use App\Tests\Support\Calendar\InMemoryHolidayRepository;
use App\Tests\Support\Calendar\InMemoryWorkScheduleRepository;
use App\Tests\Support\Project\InMemoryProjectAssignmentRepository;
use App\Tests\Support\Skill\InMemorySkillAssignmentRepository;
use App\Tests\Support\User\InMemoryUserRepository;
use PHPUnit\Framework\TestCase;

/**
 * US-040 (EPIC-004) — recherche de staffing : filtrage compétence + niveau, croisement disponibilité, gating.
 */
final class SearchStaffingTest extends TestCase
{
    private const string SKILL = '018f9c4e-0000-7000-8000-00000000abcd';

    private TenantId $tenant;
    private InMemorySkillAssignmentRepository $skillAssignments;
    private InMemoryUserRepository $users;
    private Authorizer $authorizer;
    private User $manager;
    private string $aliceId;
    private string $bobId;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $roles = new InMemoryRoleRepository();
        $roles->add(new Role($this->tenant, 'Chef de projet', [Permission::VIEW_TEAM_COMPLETENESS], DataScope::OWN_PROJECTS));
        $roles->add(new Role($this->tenant, 'Collaborateur', [Permission::VIEW_PROJECT], DataScope::OWN));

        $this->skillAssignments = new InMemorySkillAssignmentRepository();
        $this->users = new InMemoryUserRepository();
        $this->authorizer = new Authorizer($roles, new RecordingSecurityAuditLogger());

        $alice = new User($this->tenant, 'alice@agence.test', 'hash', ['Collaborateur']);
        $alice->rename('Alice', 'A');
        $bob = new User($this->tenant, 'bob@agence.test', 'hash', ['Collaborateur']);
        $bob->rename('Bob', 'B');
        $this->users->add($alice);
        $this->users->add($bob);
        $this->aliceId = $alice->id();
        $this->bobId = $bob->id();

        $this->manager = new User($this->tenant, 'marc@agence.test', 'hash', ['Chef de projet']);
    }

    public function testFiltersBySkillAndMinLevel(): void
    {
        // Alice niveau 3, Bob niveau 1 sur la compétence. Recherche niveau ≥ 2 → seule Alice.
        $this->skillAssignments->save(new SkillAssignment($this->tenant, $this->aliceId, self::SKILL, 3));
        $this->skillAssignments->save(new SkillAssignment($this->tenant, $this->bobId, self::SKILL, 1));

        $candidates = $this->search()->search($this->manager, self::SKILL, 2, '2026-07');

        self::assertCount(1, $candidates);
        self::assertSame($this->aliceId, $candidates[0]->userId);
        self::assertSame(3, $candidates[0]->skillLevel);
    }

    public function testEmptyWhenNoMatch(): void
    {
        $this->skillAssignments->save(new SkillAssignment($this->tenant, $this->aliceId, self::SKILL, 1));

        $candidates = $this->search()->search($this->manager, self::SKILL, 4, '2026-07');

        self::assertSame([], $candidates);
    }

    public function testUnauthorizedIsDenied(): void
    {
        $collaborator = new User($this->tenant, 'zoe@agence.test', 'hash', ['Collaborateur']);
        $this->expectException(AccessDeniedException::class);
        $this->search()->search($collaborator, self::SKILL, 1, '2026-07');
    }

    private function search(): SearchStaffing
    {
        $calc = new WorkingDaysCalculator(new InMemoryHolidayRepository(), new InMemoryClosurePeriodRepository(), new InMemoryWorkScheduleRepository());
        $plan = new ViewWorkloadPlan($this->authorizer, $this->users, $calc, new InMemoryAbsenceRequestRepository(), new InMemoryProjectAssignmentRepository());

        return new SearchStaffing($this->authorizer, $this->skillAssignments, $this->users, $plan);
    }
}
