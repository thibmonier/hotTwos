<?php

declare(strict_types=1);

namespace App\Domain\Skill;

use App\Domain\Tenant\TenantId;

interface SkillAssignmentRepository
{
    public function save(SkillAssignment $assignment): void;

    public function find(TenantId $tenant, string $userId, string $skillId): ?SkillAssignment;

    /**
     * @return array<string, int> skillId => nombre de collaborateurs
     */
    public function countBySkill(TenantId $tenant): array;
}
