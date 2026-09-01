<?php

declare(strict_types=1);

namespace App\Domain\Period;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

/**
 * Période comptable d'un tenant (US-057) : un mois calendaire `YYYY-MM` et son statut de clôture.
 *
 * La clôture verrouille les imputations de la période (INV-7) : plus aucune modification sans
 * réouverture formelle (RG-TMP-6). Portée par tenant (INV-1). Unicité (tenant, période).
 */
#[ORM\Entity]
#[ORM\Table(name: 'accounting_period')]
#[ORM\UniqueConstraint(name: 'uniq_period_tenant_month', columns: ['tenant_id', 'period'])]
class AccountingPeriod implements TenantOwned
{
    private const string PERIOD_FORMAT = '/^\d{4}-(0[1-9]|1[0-2])$/';

    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    #[ORM\Column(name: 'closed_by', type: 'guid', nullable: true)]
    private ?string $closedBy = null;

    #[ORM\Column(name: 'closed_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $closedAt = null;

    /**
     * @param string $period mois au format YYYY-MM (validé)
     */
    public function __construct(
        TenantId $tenantId,
        #[ORM\Column(length: 7)]
        private string $period,
        #[ORM\Column(name: 'status', length: 20, enumType: PeriodStatus::class)]
        private PeriodStatus $status = PeriodStatus::OPEN,
    ) {
        if (1 !== preg_match(self::PERIOD_FORMAT, $period)) {
            throw new InvalidArgumentException(sprintf('Période invalide « %s » (attendu YYYY-MM).', $period));
        }

        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
    }

    /**
     * Clôture la période : verrouille les imputations et fige l'auteur/horodatage (CA-1).
     * Idempotente sur une période déjà clôturée (aucune double-clôture).
     */
    public function close(string $actorId, DateTimeImmutable $at): void
    {
        if (PeriodStatus::CLOSED === $this->status) {
            return;
        }

        $this->status = PeriodStatus::CLOSED;
        $this->closedBy = $actorId;
        $this->closedAt = $at;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }

    public function period(): string
    {
        return $this->period;
    }

    public function status(): PeriodStatus
    {
        return $this->status;
    }

    public function isClosed(): bool
    {
        return PeriodStatus::CLOSED === $this->status;
    }

    public function closedBy(): ?string
    {
        return $this->closedBy;
    }

    public function closedAt(): ?DateTimeImmutable
    {
        return $this->closedAt;
    }
}
