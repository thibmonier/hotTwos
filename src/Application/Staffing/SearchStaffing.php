<?php

declare(strict_types=1);

namespace App\Application\Staffing;

use App\Domain\Authorization\Permission;
use App\Application\Authorization\Authorizer;
use App\Domain\Skill\SkillAssignmentRepository;
use App\Domain\User\User;
use App\Domain\User\UserRepository;

/**
 * US-040 (EPIC-004) — recherche de staffing : collaborateurs maîtrisant une compétence (niveau minimal)
 * et leur disponibilité sur la période (réutilise {@see ViewWorkloadPlan}). Recherche simple (ET des
 * critères), sans scoring avancé. Aucun coût (HAB-1).
 */
final readonly class SearchStaffing
{
    public function __construct(
        private Authorizer $authorizer,
        private SkillAssignmentRepository $skillAssignments,
        private UserRepository $users,
        private ViewWorkloadPlan $workloadPlan,
    ) {
    }

    /**
     * @return list<StaffingCandidate>
     */
    public function search(User $actor, string $skillId, int $minLevel, string $period): array
    {
        $this->authorizer->ensureCan($actor, Permission::VIEW_TEAM_COMPLETENESS);

        $tenant = $actor->tenantId();
        $levels = $this->skillAssignments->levelsBySkillAtLeast($tenant, $skillId, $minLevel);
        if ([] === $levels) {
            return [];
        }

        $names = $this->users->findDisplayNamesByIds($tenant, array_keys($levels));

        // Disponibilité : réutilise le plan de charge (capacité − charge ferme) de la période.
        $available = [];
        foreach ($this->workloadPlan->forMonth($actor, $period)->lines as $line) {
            $available[$line->userId] = $line->availableDays();
        }

        $candidates = [];
        foreach ($levels as $userId => $level) {
            $candidates[] = new StaffingCandidate($userId, $names[$userId] ?? $userId, $level, $available[$userId] ?? 0);
        }

        usort($candidates, static fn (StaffingCandidate $a, StaffingCandidate $b): int => $b->availableDays <=> $a->availableDays);

        return $candidates;
    }
}
