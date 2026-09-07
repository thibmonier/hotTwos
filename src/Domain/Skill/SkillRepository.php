<?php

declare(strict_types=1);

namespace App\Domain\Skill;

use App\Domain\Tenant\TenantId;

interface SkillRepository
{
    public function save(Skill $skill): void;

    public function find(TenantId $tenant, string $id): ?Skill;

    /**
     * @return list<Skill>
     */
    public function findForTenant(TenantId $tenant, bool $includeInactive = true): array;

    public function existsByLabel(TenantId $tenant, SkillCategory $category, string $normalizedLabel): bool;
}
