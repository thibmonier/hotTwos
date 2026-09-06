<?php

declare(strict_types=1);

namespace App\Domain\Project;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Ligne de budget de charge d'un lot ventilée **par profil** (US-078, EF-PRJ-9) : un nombre de jours
 * pour un profil du référentiel (EPIC-001). L'équivalent en € de vente / € de coût est dérivé des taux
 * en vigueur ({@see \App\Domain\Pricing\RateResolver}) — cf. {@see ProfileBudgetCalculator}. Coexiste
 * avec le budget global du lot (`ProjectLot::budgetDays`/`budgetCents`), migration douce.
 */
#[ORM\Entity]
#[ORM\Table(name: 'lot_profile_budget')]
#[ORM\Index(name: 'idx_lot_profile_budget_tenant_lot', columns: ['tenant_id', 'lot_id'])]
class LotProfileBudget implements TenantOwned
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    public function __construct(
        TenantId $tenantId,
        #[ORM\Column(name: 'lot_id', type: 'guid')]
        private string $lotId,
        #[ORM\Column(name: 'profile_id', type: 'guid')]
        private string $profileId,
        #[ORM\Column(name: 'days', type: 'integer')]
        private int $days,
    ) {
        $this->guardDays($days);
        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
    }

    public function changeDays(int $days): void
    {
        $this->guardDays($days);
        $this->days = $days;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }

    public function lotId(): string
    {
        return $this->lotId;
    }

    public function profileId(): string
    {
        return $this->profileId;
    }

    public function days(): int
    {
        return $this->days;
    }

    private function guardDays(int $days): void
    {
        if ($days < 0) {
            throw new ProjectException('Le budget de charge par profil ne peut pas être négatif.');
        }
    }
}
