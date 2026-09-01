<?php

declare(strict_types=1);

namespace App\Domain\Organization;

use App\Domain\Shared\EffectivePeriod;
use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Rattachement historisé d'un collaborateur à une unité organisationnelle (US-010, EF-REF-2).
 *
 * La période de validité (VO {@see EffectivePeriod}, à date d'effet) est **décomposée** en deux
 * colonnes date afin que le value object reste pur (sans annotation ORM) : elle est reconstruite
 * par {@see period()}. Historisation : les rattachements passés restent liés à leur ancienne
 * unité même après réorganisation (RG-REF-1). Portée par tenant (INV-1).
 */
#[ORM\Entity]
#[ORM\Table(name: 'org_membership')]
#[ORM\Index(name: 'idx_org_membership_tenant_user', columns: ['tenant_id', 'user_id'])]
#[ORM\Index(name: 'idx_org_membership_tenant_unit', columns: ['tenant_id', 'org_unit_id'])]
class OrgMembership implements TenantOwned
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    #[ORM\Column(name: 'effective_from', type: 'date_immutable')]
    private DateTimeImmutable $effectiveFrom;

    #[ORM\Column(name: 'effective_to', type: 'date_immutable', nullable: true)]
    private ?DateTimeImmutable $effectiveTo;

    public function __construct(TenantId $tenantId, #[ORM\Column(name: 'user_id', type: 'guid')]
        private string $userId, #[ORM\Column(name: 'org_unit_id', type: 'guid')]
        private string $orgUnitId, EffectivePeriod $period)
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
        $this->effectiveFrom = $period->from();
        $this->effectiveTo = $period->to();
    }

    public function id(): string
    {
        return $this->id;
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function orgUnitId(): string
    {
        return $this->orgUnitId;
    }

    public function period(): EffectivePeriod
    {
        return $this->effectiveTo instanceof DateTimeImmutable
            ? EffectivePeriod::between($this->effectiveFrom, $this->effectiveTo)
            : EffectivePeriod::since($this->effectiveFrom);
    }
}
