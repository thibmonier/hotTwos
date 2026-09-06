<?php

declare(strict_types=1);

namespace App\Domain\Project;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Avenant budgétaire d'un projet (US-033, EF-PRJ-8). Enregistrement **immuable** et daté d'une révision
 * du budget : un delta en coût (charge) et/ou en montant (CA), un **motif obligatoire** (RG-PRJ-4) et
 * l'auteur. Le budget courant se dérive du budget initial + la somme des avenants ({@see CurrentProjectBudget}) ;
 * les imputations/valorisations historiques ne sont jamais altérées (INV-2/INV-3).
 */
#[ORM\Entity]
#[ORM\Table(name: 'budget_amendment')]
#[ORM\Index(name: 'idx_budget_amendment_tenant_project', columns: ['tenant_id', 'project_id'])]
class BudgetAmendment implements TenantOwned
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    private function __construct(
        TenantId $tenantId,
        #[ORM\Column(name: 'project_id', type: 'guid')]
        private string $projectId,
        #[ORM\Column(name: 'delta_cost_cents', type: 'integer')]
        private int $deltaCostCents,
        #[ORM\Column(name: 'delta_revenue_cents', type: 'integer')]
        private int $deltaRevenueCents,
        #[ORM\Column(name: 'reason', length: 255)]
        private string $reason,
        #[ORM\Column(name: 'author_id', type: 'guid')]
        private string $authorId,
        #[ORM\Column(name: 'recorded_at', type: 'datetime_immutable')]
        private DateTimeImmutable $recordedAt,
    ) {
        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
    }

    /**
     * Enregistre un avenant. Au moins un delta doit être non nul et le motif est obligatoire (RG-PRJ-4).
     */
    public static function record(
        TenantId $tenantId,
        string $projectId,
        int $deltaCostCents,
        int $deltaRevenueCents,
        string $reason,
        string $authorId,
        DateTimeImmutable $recordedAt,
    ): self {
        if (0 === $deltaCostCents && 0 === $deltaRevenueCents) {
            throw new ProjectException('Un avenant doit modifier le budget de charge et/ou de montant.');
        }
        if ('' === trim($reason)) {
            throw new ProjectException('Un motif est obligatoire pour un avenant (RG-PRJ-4).');
        }

        return new self($tenantId, $projectId, $deltaCostCents, $deltaRevenueCents, trim($reason), $authorId, $recordedAt);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }

    public function projectId(): string
    {
        return $this->projectId;
    }

    public function deltaCostCents(): int
    {
        return $this->deltaCostCents;
    }

    public function deltaRevenueCents(): int
    {
        return $this->deltaRevenueCents;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function authorId(): string
    {
        return $this->authorId;
    }

    public function recordedAt(): DateTimeImmutable
    {
        return $this->recordedAt;
    }
}
