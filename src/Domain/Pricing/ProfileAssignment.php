<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

use App\Domain\Shared\EffectivePeriod;
use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Rattachement d'un collaborateur à un profil de tarification, historisé à date d'effet
 * (US-011/US-060). Pivot de la valorisation : à une date de prestation, il désigne le profil
 * — donc le tarif via {@see RateResolver} — applicable au collaborateur. Portée par tenant.
 */
#[ORM\Entity]
#[ORM\Table(name: 'profile_assignment')]
#[ORM\Index(name: 'idx_profile_assignment_tenant_user', columns: ['tenant_id', 'user_id'])]
class ProfileAssignment implements TenantOwned
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

    public function __construct(
        TenantId $tenantId,
        #[ORM\Column(name: 'user_id', type: 'guid')]
        private string $userId,
        #[ORM\Column(name: 'profile_id', type: 'guid')]
        private string $profileId,
        EffectivePeriod $period,
    ) {
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

    public function profileId(): string
    {
        return $this->profileId;
    }

    public function period(): EffectivePeriod
    {
        return $this->effectiveTo instanceof DateTimeImmutable
            ? EffectivePeriod::between($this->effectiveFrom, $this->effectiveTo)
            : EffectivePeriod::since($this->effectiveFrom);
    }
}
