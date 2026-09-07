<?php

declare(strict_types=1);

namespace App\Domain\Skill;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * US-013 (EF-REF-10) — association d'une compétence à un collaborateur, avec un niveau (index dans
 * l'échelle du tenant). Unicité par (tenant, collaborateur, compétence).
 */
#[ORM\Entity]
#[ORM\Table(name: 'skill_assignment')]
#[ORM\UniqueConstraint(name: 'uniq_skill_assignment', columns: ['tenant_id', 'user_id', 'skill_id'])]
class SkillAssignment implements TenantOwned
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    public function __construct(
        TenantId $tenantId,
        #[ORM\Column(name: 'user_id', type: 'guid')]
        private string $userId,
        #[ORM\Column(name: 'skill_id', type: 'guid')]
        private string $skillId,
        #[ORM\Column(name: 'level', type: 'smallint')]
        private int $level,
    ) {
        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
    }

    public function reassign(int $level): void
    {
        $this->level = $level;
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function skillId(): string
    {
        return $this->skillId;
    }

    public function level(): int
    {
        return $this->level;
    }
}
