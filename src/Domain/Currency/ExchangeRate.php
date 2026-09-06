<?php

declare(strict_types=1);

namespace App\Domain\Currency;

use App\Domain\Shared\EffectivePeriod;
use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Taux de change daté d'une devise vers la **devise de référence** du tenant (US-016, EF-REF-22).
 * Le taux est stocké en **millièmes** (entier) pour éviter les flottants : 1,05 → 1050 (1 unité de la
 * devise = 1,05 unité de référence). Historisé à date d'effet (INV-2, cohérent avec Pricing).
 */
#[ORM\Entity]
#[ORM\Table(name: 'exchange_rate')]
#[ORM\Index(name: 'idx_exchange_rate_tenant_code', columns: ['tenant_id', 'code'])]
class ExchangeRate implements TenantOwned
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
        #[ORM\Column(name: 'code', length: 3)]
        private string $code,
        EffectivePeriod $period,
        #[ORM\Column(name: 'rate_to_reference_millis', type: 'integer')]
        private int $rateToReferenceMillis,
    ) {
        if ($rateToReferenceMillis <= 0) {
            throw new CurrencyException('Le taux de change doit être strictement positif.');
        }
        $this->code = strtoupper(trim($code));
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

    public function code(): string
    {
        return $this->code;
    }

    public function period(): EffectivePeriod
    {
        return $this->effectiveTo instanceof DateTimeImmutable
            ? EffectivePeriod::between($this->effectiveFrom, $this->effectiveTo)
            : EffectivePeriod::since($this->effectiveFrom);
    }

    public function rateToReferenceMillis(): int
    {
        return $this->rateToReferenceMillis;
    }
}
