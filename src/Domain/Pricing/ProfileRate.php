<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

use App\Domain\Shared\EffectivePeriod;
use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

/**
 * Entrée tarifaire historisée d'un profil (US-011, EF-REF-5, INV-2).
 *
 * Coût de revient et taux de vente en **centimes entiers** (jamais de flottant), valides sur
 * une période à date d'effet ({@see EffectivePeriod} décomposé en deux colonnes pour garder le
 * VO pur). Une révision ne modifie jamais les entrées passées : on ajoute une nouvelle entrée.
 * Portée par tenant (INV-1).
 */
#[ORM\Entity]
#[ORM\Table(name: 'profile_rate')]
#[ORM\Index(name: 'idx_profile_rate_tenant_profile', columns: ['tenant_id', 'profile_id'])]
class ProfileRate implements TenantOwned
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

    #[ORM\Column(name: 'cost_price_cents', type: 'integer')]
    private int $costPriceCents;

    #[ORM\Column(name: 'selling_price_cents', type: 'integer')]
    private int $sellingPriceCents;

    public function __construct(
        TenantId $tenantId,
        #[ORM\Column(name: 'profile_id', type: 'guid')]
        private string $profileId,
        EffectivePeriod $period,
        int $costPriceCents,
        int $sellingPriceCents,
    ) {
        $this->guardNonNegative($costPriceCents, 'Le coût de revient');
        $this->guardNonNegative($sellingPriceCents, 'Le taux de vente');

        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
        $this->effectiveFrom = $period->from();
        $this->effectiveTo = $period->to();
        $this->costPriceCents = $costPriceCents;
        $this->sellingPriceCents = $sellingPriceCents;
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

    public function period(): EffectivePeriod
    {
        return $this->effectiveTo instanceof DateTimeImmutable
            ? EffectivePeriod::between($this->effectiveFrom, $this->effectiveTo)
            : EffectivePeriod::since($this->effectiveFrom);
    }

    public function costPriceCents(): int
    {
        return $this->costPriceCents;
    }

    public function sellingPriceCents(): int
    {
        return $this->sellingPriceCents;
    }

    private function guardNonNegative(int $cents, string $label): void
    {
        if ($cents < 0) {
            throw new InvalidArgumentException(sprintf('%s ne peut pas être négatif.', $label));
        }
    }
}
