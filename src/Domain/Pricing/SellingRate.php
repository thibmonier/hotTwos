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
 * Surcharge de **taux de vente** d'un profil au niveau **client** ou **projet** (US-015, EF-REF-19),
 * historisée à date d'effet (INV-2). Le niveau profil (défaut) reste porté par {@see ProfileRate}.
 * La résolution de priorité (projet > client > profil) est faite par {@see SellingRateResolver}.
 */
#[ORM\Entity]
#[ORM\Table(name: 'selling_rate')]
#[ORM\Index(name: 'idx_selling_rate_lookup', columns: ['tenant_id', 'profile_id', 'scope', 'scope_ref_id'])]
class SellingRate implements TenantOwned
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
        #[ORM\Column(name: 'profile_id', type: 'guid')]
        private string $profileId,
        #[ORM\Column(name: 'scope', length: 10, enumType: RateScope::class)]
        private RateScope $scope,
        #[ORM\Column(name: 'scope_ref_id', type: 'guid')]
        private string $scopeRefId,
        EffectivePeriod $period,
        #[ORM\Column(name: 'selling_price_cents', type: 'integer')]
        private int $sellingPriceCents,
    ) {
        if ($sellingPriceCents <= 0) {
            throw new PricingException('Le taux de vente doit être strictement positif.');
        }
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

    public function profileId(): string
    {
        return $this->profileId;
    }

    public function scope(): RateScope
    {
        return $this->scope;
    }

    public function scopeRefId(): string
    {
        return $this->scopeRefId;
    }

    public function period(): EffectivePeriod
    {
        return $this->effectiveTo instanceof DateTimeImmutable
            ? EffectivePeriod::between($this->effectiveFrom, $this->effectiveTo)
            : EffectivePeriod::since($this->effectiveFrom);
    }

    public function sellingPriceCents(): int
    {
        return $this->sellingPriceCents;
    }
}
