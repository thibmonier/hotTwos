<?php

declare(strict_types=1);

namespace App\Domain\Skill;

use App\Domain\Tenant\TenantId;

interface SkillLevelScaleRepository
{
    public function findForTenant(TenantId $tenant): ?SkillLevelScale;

    public function save(SkillLevelScale $scale): void;
}
