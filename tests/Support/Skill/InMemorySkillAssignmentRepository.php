<?php

declare(strict_types=1);

namespace App\Tests\Support\Skill;

use App\Domain\Skill\SkillAssignment;
use App\Domain\Skill\SkillAssignmentRepository;
use App\Domain\Tenant\TenantId;

final class InMemorySkillAssignmentRepository implements SkillAssignmentRepository
{
    /** @var list<SkillAssignment> */
    public array $assignments = [];

    public function save(SkillAssignment $assignment): void
    {
        $this->assignments[] = $assignment;
    }

    public function find(TenantId $tenant, string $userId, string $skillId): ?SkillAssignment
    {
        foreach ($this->assignments as $a) {
            if ($a->tenantId()->equals($tenant) && $a->userId() === $userId && $a->skillId() === $skillId) {
                return $a;
            }
        }

        return null;
    }

    public function countBySkill(TenantId $tenant): array
    {
        $map = [];
        foreach ($this->assignments as $a) {
            if ($a->tenantId()->equals($tenant)) {
                $map[$a->skillId()] = ($map[$a->skillId()] ?? 0) + 1;
            }
        }

        return $map;
    }

    public function levelsBySkillAtLeast(TenantId $tenant, string $skillId, int $minLevel): array
    {
        $map = [];
        foreach ($this->assignments as $a) {
            if ($a->tenantId()->equals($tenant) && $a->skillId() === $skillId && $a->level() >= $minLevel) {
                $map[$a->userId()] = $a->level();
            }
        }

        return $map;
    }
}
