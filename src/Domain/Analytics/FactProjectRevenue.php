<?php

declare(strict_types=1);

namespace App\Domain\Analytics;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Table de faits « chiffre d'affaires par projet » du schéma en étoile (US-005).
 * Porte le discriminant tenant (double barrière : filtre ORM + RLS — CA-4).
 *
 * Grain : (tenant, période, projet). Montant agrégé en centimes entiers (INV-2).
 * Écrite exclusivement par le projecteur (ARC-111) ; toute écriture directe hors canal
 * événementiel est rejetée en base (trigger — CA-6).
 */
#[ORM\Entity]
#[ORM\Table(name: 'fact_project_revenue')]
#[ORM\UniqueConstraint(name: 'uniq_fact_revenue_grain', columns: ['tenant_id', 'period', 'project_ref'])]
class FactProjectRevenue implements TenantOwned
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    public function __construct(TenantId $tenantId, #[ORM\Column(length: 7)]
        private string $period, #[ORM\Column(name: 'project_ref', length: 100)]
        private string $projectRef, #[ORM\Column(name: 'amount_cents', type: 'integer')]
        private int $amountCents)
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
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

    public function amountCents(): int
    {
        return $this->amountCents;
    }
}
