<?php

declare(strict_types=1);

namespace App\Domain\Valuation;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Valorisation figée d'une imputation (US-060, INV-2/INV-3).
 *
 * Le taux appliqué est **copié** ici au moment de la validation (`snapshot*`), pas seulement lu
 * dans la table des tarifs : une révision tarifaire ultérieure ne réécrit **jamais** une
 * valorisation passée. Montants en centimes entiers. Entité immuable (aucun mutateur) : une
 * re-valorisation crée une nouvelle instance. Portée par tenant (INV-1).
 */
#[ORM\Entity]
#[ORM\Table(name: 'time_entry_valuation')]
#[ORM\UniqueConstraint(name: 'uniq_valuation_tenant_entry', columns: ['tenant_id', 'time_entry_id'])]
#[ORM\Index(name: 'idx_valuation_tenant_status', columns: ['tenant_id', 'status'])]
class TimeEntryValuation implements TenantOwned
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    private function __construct(
        TenantId $tenantId,
        #[ORM\Column(name: 'time_entry_id', type: 'guid')]
        private string $timeEntryId,
        #[ORM\Column(name: 'status', length: 20, enumType: ValuationStatus::class)]
        private ValuationStatus $status,
        #[ORM\Column(name: 'cost_cents', type: 'integer')]
        private int $costCents,
        #[ORM\Column(name: 'revenue_cents', type: 'integer')]
        private int $revenueCents,
        #[ORM\Column(name: 'snapshot_cost_rate_cents', type: 'integer', nullable: true)]
        private ?int $snapshotCostRateCents,
        #[ORM\Column(name: 'snapshot_selling_rate_cents', type: 'integer', nullable: true)]
        private ?int $snapshotSellingRateCents,
        #[ORM\Column(name: 'snapshot_rate_date', type: 'date_immutable', nullable: true)]
        private ?DateTimeImmutable $snapshotRateDate,
        #[ORM\Column(name: 'valued_at', type: 'datetime_immutable')]
        private DateTimeImmutable $valuedAt,
    ) {
        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
    }

    public static function valued(
        TenantId $tenantId,
        string $timeEntryId,
        int $costCents,
        int $revenueCents,
        int $snapshotCostRateCents,
        int $snapshotSellingRateCents,
        DateTimeImmutable $rateDate,
        DateTimeImmutable $valuedAt,
    ): self {
        return new self(
            $tenantId,
            $timeEntryId,
            ValuationStatus::VALUED,
            $costCents,
            $revenueCents,
            $snapshotCostRateCents,
            $snapshotSellingRateCents,
            $rateDate,
            $valuedAt,
        );
    }

    public static function missingRate(TenantId $tenantId, string $timeEntryId, DateTimeImmutable $valuedAt): self
    {
        return new self($tenantId, $timeEntryId, ValuationStatus::MISSING_RATE, 0, 0, null, null, null, $valuedAt);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }

    public function timeEntryId(): string
    {
        return $this->timeEntryId;
    }

    public function status(): ValuationStatus
    {
        return $this->status;
    }

    public function costCents(): int
    {
        return $this->costCents;
    }

    public function revenueCents(): int
    {
        return $this->revenueCents;
    }

    public function snapshotCostRateCents(): ?int
    {
        return $this->snapshotCostRateCents;
    }

    public function snapshotSellingRateCents(): ?int
    {
        return $this->snapshotSellingRateCents;
    }

    public function snapshotRateDate(): ?DateTimeImmutable
    {
        return $this->snapshotRateDate;
    }

    public function valuedAt(): DateTimeImmutable
    {
        return $this->valuedAt;
    }
}
