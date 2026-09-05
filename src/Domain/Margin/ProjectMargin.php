<?php

declare(strict_types=1);

namespace App\Domain\Margin;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Marge réelle d'un projet **figée à la clôture** d'une période (US-071, INV-2).
 *
 * Snapshot au grain (tenant, période, projet), calqué sur {@see \App\Domain\Valuation\TimeEntryValuation} :
 * CA reconnu et coût valorisé sont **copiés** ici à la clôture, jamais recalculés par une révision de
 * taux ultérieure — la marge d'une période clôturée est opposable et non-rétroactive. Une valorisation
 * incomplète (imputations `MISSING_RATE`, CA-4) marque la ligne « partielle ». Montants en centimes
 * entiers. Entité immuable (aucun mutateur) : un nouveau figeage crée une nouvelle instance.
 */
#[ORM\Entity]
#[ORM\Table(name: 'project_margin')]
#[ORM\UniqueConstraint(name: 'uniq_margin_tenant_period_project', columns: ['tenant_id', 'period', 'project_ref'])]
#[ORM\Index(name: 'idx_margin_tenant_period', columns: ['tenant_id', 'period'])]
class ProjectMargin implements TenantOwned
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    private function __construct(
        TenantId $tenantId,
        #[ORM\Column(name: 'period', length: 7)]
        private string $period,
        #[ORM\Column(name: 'project_ref', length: 100)]
        private string $projectRef,
        #[ORM\Column(name: 'project_name', length: 255)]
        private string $projectName,
        #[ORM\Column(name: 'revenue_cents', type: 'integer')]
        private int $revenueCents,
        #[ORM\Column(name: 'cost_cents', type: 'integer')]
        private int $costCents,
        #[ORM\Column(name: 'valued_count', type: 'integer')]
        private int $valuedCount,
        #[ORM\Column(name: 'unvalued_count', type: 'integer')]
        private int $unvaluedCount,
        #[ORM\Column(name: 'partial', type: 'boolean')]
        private bool $partial,
        #[ORM\Column(name: 'frozen_at', type: 'datetime_immutable')]
        private DateTimeImmutable $frozenAt,
    ) {
        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
    }

    /**
     * Fige la marge d'un projet à la clôture.
     *
     * @param int $valuedCount   nombre d'imputations valorisées prises en compte
     * @param int $unvaluedCount nombre d'imputations non valorisées (MISSING_RATE, CA-4)
     */
    public static function freeze(
        TenantId $tenantId,
        string $period,
        string $projectRef,
        string $projectName,
        int $revenueCents,
        int $costCents,
        int $valuedCount,
        int $unvaluedCount,
        DateTimeImmutable $frozenAt,
    ): self {
        return new self(
            $tenantId,
            $period,
            $projectRef,
            $projectName,
            $revenueCents,
            $costCents,
            $valuedCount,
            $unvaluedCount,
            $unvaluedCount > 0,
            $frozenAt,
        );
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

    public function projectRef(): string
    {
        return $this->projectRef;
    }

    public function projectName(): string
    {
        return $this->projectName;
    }

    public function revenueCents(): int
    {
        return $this->revenueCents;
    }

    public function costCents(): int
    {
        return $this->costCents;
    }

    /**
     * Marge figée = CA reconnu − coût valorisé (définitionnelle ; le taux de marge, non trivial,
     * vit dans {@see MarginCalculator}).
     */
    public function marginCents(): int
    {
        return $this->revenueCents - $this->costCents;
    }

    public function valuedCount(): int
    {
        return $this->valuedCount;
    }

    public function unvaluedCount(): int
    {
        return $this->unvaluedCount;
    }

    public function isPartial(): bool
    {
        return $this->partial;
    }

    public function frozenAt(): DateTimeImmutable
    {
        return $this->frozenAt;
    }
}
