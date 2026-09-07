<?php

declare(strict_types=1);

namespace App\Application\Staffing;

use App\Application\Authorization\Authorizer;
use App\Domain\Absence\AbsenceRequest;
use App\Domain\Absence\AbsenceRequestRepository;
use App\Domain\Authorization\Permission;
use App\Domain\Calendar\WorkingDaysCalculator;
use App\Domain\Project\ProjectAssignmentRepository;
use App\Domain\Shared\CalendarMonth;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Domain\User\UserRepository;
use DateTimeImmutable;

/**
 * US-041 (EPIC-004, OBJ-4) — plan de charge : capacité (jours ouvrés nets d'absences, par régime de
 * travail) vs charge ferme (jours affectés) par collaborateur sur un mois. Aucun coût (HAB-1). La
 * charge probable (pipeline pondéré, INV-5) est hors périmètre (dépend d'EPIC-006 CRM).
 */
final readonly class ViewWorkloadPlan
{
    public function __construct(
        private Authorizer $authorizer,
        private UserRepository $users,
        private WorkingDaysCalculator $workingDays,
        private AbsenceRequestRepository $absences,
        private ProjectAssignmentRepository $assignments,
    ) {
    }

    public function forMonth(User $actor, string $period): WorkloadPlanView
    {
        $this->authorizer->ensureCan($actor, Permission::VIEW_TEAM_COMPLETENESS);

        $tenant = $actor->tenantId();
        [$from, $to] = CalendarMonth::bounds($period);

        $userIds = $this->users->findIdsByTenant($tenant);
        $names = $this->users->findDisplayNamesByIds($tenant, $userIds);
        $firmByUser = $this->assignments->plannedDaysByUser($tenant, $from, $to);

        $lines = [];
        foreach ($userIds as $userId) {
            $capacity = max(0, $this->workingDays->workingDaysForUser($tenant, $userId, $from, $to) - $this->absenceDays($tenant, $userId, $from, $to));
            $lines[] = new WorkloadLine($userId, $names[$userId] ?? $userId, $capacity, $firmByUser[$userId] ?? 0);
        }

        usort($lines, static fn (WorkloadLine $a, WorkloadLine $b): int => $b->firmLoadDays <=> $a->firmLoadDays);

        return new WorkloadPlanView($period, $lines);
    }

    private function absenceDays(TenantId $tenant, string $userId, DateTimeImmutable $from, DateTimeImmutable $to): int
    {
        $lastDay = $to->modify('-1 day');
        $absences = $this->absences->findValidatedOverlapping($tenant, $userId, $from, $lastDay);

        $count = 0;
        for ($day = $from; $day < $to; $day = $day->modify('+1 day')) {
            if ($this->workingDays->isWorkingDayForUser($tenant, $userId, $day) && $this->covers($absences, $day)) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * @param list<AbsenceRequest> $absences
     */
    private function covers(array $absences, DateTimeImmutable $day): bool
    {
        return array_any($absences, static fn (AbsenceRequest $absence): bool => $absence->coversDay($day));
    }
}
