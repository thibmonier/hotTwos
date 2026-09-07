<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Staffing;

use App\Application\Authorization\Authorizer;
use App\Application\Staffing\ViewWorkloadPlan;
use App\Domain\Authorization\AccessDeniedException;
use App\Domain\Authorization\DataScope;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\Role;
use App\Domain\Calendar\WorkSchedule;
use App\Domain\Calendar\WorkingDaysCalculator;
use App\Domain\Project\ProjectAssignment;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Tests\Support\Absence\InMemoryAbsenceRequestRepository;
use App\Tests\Support\Authorization\InMemoryRoleRepository;
use App\Tests\Support\Authorization\RecordingSecurityAuditLogger;
use App\Tests\Support\Calendar\InMemoryClosurePeriodRepository;
use App\Tests\Support\Calendar\InMemoryHolidayRepository;
use App\Tests\Support\Calendar\InMemoryWorkScheduleRepository;
use App\Tests\Support\Project\InMemoryProjectAssignmentRepository;
use App\Tests\Support\User\InMemoryUserRepository;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

/**
 * US-041 (EPIC-004) — plan de charge : capacité (jours ouvrés nets, par régime) vs charge ferme
 * (affectations), détection de surcharge, gating.
 */
final class ViewWorkloadPlanTest extends TestCase
{
    private TenantId $tenant;
    private InMemoryUserRepository $users;
    private InMemoryAbsenceRequestRepository $absences;
    private InMemoryProjectAssignmentRepository $assignments;
    private InMemoryWorkScheduleRepository $schedules;
    private Authorizer $authorizer;
    private User $manager;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $roles = new InMemoryRoleRepository();
        $roles->add(new Role($this->tenant, 'Chef de projet', [Permission::VIEW_TEAM_COMPLETENESS], DataScope::OWN_PROJECTS));
        $roles->add(new Role($this->tenant, 'Collaborateur', [Permission::VIEW_PROJECT], DataScope::OWN));

        $this->users = new InMemoryUserRepository();
        $this->absences = new InMemoryAbsenceRequestRepository();
        $this->assignments = new InMemoryProjectAssignmentRepository();
        $this->schedules = new InMemoryWorkScheduleRepository();
        $this->authorizer = new Authorizer($roles, new RecordingSecurityAuditLogger());

        $alice = new User($this->tenant, 'alice@agence.test', 'hash', ['Collaborateur']);
        $alice->rename('Alice', 'A');
        $bob = new User($this->tenant, 'bob@agence.test', 'hash', ['Collaborateur']);
        $bob->rename('Bob', 'B');
        // Réassigner les ids connus pour des assertions stables.
        $this->users->add($alice);
        $this->users->add($bob);
        $this->aliceId = $alice->id();
        $this->bobId = $bob->id();

        $this->manager = new User($this->tenant, 'marc@agence.test', 'hash', ['Chef de projet']);
    }

    private string $aliceId;
    private string $bobId;

    public function testCapacityVsFirmLoadAndOverload(): void
    {
        // Juillet 2026 : 23 jours ouvrés (pas de férié configuré ici).
        // Alice : 10 j affectés → sous-charge ; Bob : 30 j affectés → surcharge.
        $this->assignments->save(new ProjectAssignment($this->tenant, 'p1', $this->aliceId, 'Dev', 10, $this->date('2026-07-01'), $this->date('2026-07-31')));
        $this->assignments->save(new ProjectAssignment($this->tenant, 'p1', $this->bobId, 'Dev', 30, $this->date('2026-07-01'), $this->date('2026-07-31')));

        $plan = $this->view()->forMonth($this->manager, '2026-07');

        $byUser = [];
        foreach ($plan->lines as $line) {
            $byUser[$line->userId] = $line;
        }

        $workingDays = $this->weekdays('2026-07');
        self::assertSame($workingDays, $byUser[$this->aliceId]->capacityDays);
        self::assertSame(10, $byUser[$this->aliceId]->firmLoadDays);
        self::assertFalse($byUser[$this->aliceId]->isOverloaded());
        self::assertTrue($byUser[$this->bobId]->isOverloaded());
    }

    public function testPartTimeReducesCapacity(): void
    {
        // Alice à 4 jours/semaine (Lun-Jeu) → capacité réduite.
        $this->schedules->save(new WorkSchedule($this->tenant, $this->aliceId, [1, 2, 3, 4]));

        $plan = $this->view()->forMonth($this->manager, '2026-07');
        $alice = null;
        foreach ($plan->lines as $line) {
            if ($line->userId === $this->aliceId) {
                $alice = $line;
            }
        }

        self::assertNotNull($alice);
        self::assertLessThan($this->weekdays('2026-07'), $alice->capacityDays);
    }

    public function testUnauthorizedIsDenied(): void
    {
        $collaborator = new User($this->tenant, 'zoe@agence.test', 'hash', ['Collaborateur']);
        $this->expectException(AccessDeniedException::class);
        $this->view()->forMonth($collaborator, '2026-07');
    }

    private function view(): ViewWorkloadPlan
    {
        $calc = new WorkingDaysCalculator(new InMemoryHolidayRepository(), new InMemoryClosurePeriodRepository(), $this->schedules);

        return new ViewWorkloadPlan($this->authorizer, $this->users, $calc, $this->absences, $this->assignments);
    }

    private function weekdays(string $month): int
    {
        $from = new DateTimeImmutable($month.'-01 00:00:00', new DateTimeZone('UTC'));
        $to = $from->modify('+1 month');
        $count = 0;
        for ($day = $from; $day < $to; $day = $day->modify('+1 day')) {
            if ((int) $day->format('N') <= 5) {
                ++$count;
            }
        }

        return $count;
    }

    private function date(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value.' 00:00:00', new DateTimeZone('UTC'));
    }
}
