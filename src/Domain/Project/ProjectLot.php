<?php

declare(strict_types=1);

namespace App\Domain\Project;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Lot d'un projet (US-031, EF-PRJ-2) : budget **bidimensionnel** (charge en jours + montant en €) et
 * arborescence à 2 niveaux via `parentLotId` (un lot racine ou un sous-lot). Portée par tenant.
 */
#[ORM\Entity]
#[ORM\Table(name: 'project_lot')]
#[ORM\Index(name: 'idx_lot_tenant_project', columns: ['tenant_id', 'project_id'])]
class ProjectLot implements TenantOwned
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    /** Avancement physique déclaré (0-100 %), distinct de la consommation valorisée (US-035, INV-4). */
    #[ORM\Column(name: 'physical_progress_percent', type: 'integer', nullable: true)]
    private ?int $physicalProgressPercent = null;

    /** Reste-à-faire déclaré en jours (US-035, INV-4) ; indépendant de l'avancement. */
    #[ORM\Column(name: 'remaining_work_days', type: 'integer', nullable: true)]
    private ?int $remainingWorkDays = null;

    public function __construct(
        TenantId $tenantId,
        #[ORM\Column(name: 'project_id', type: 'guid')]
        private string $projectId,
        #[ORM\Column(name: 'name', length: 150)]
        private string $name,
        #[ORM\Column(name: 'budget_days', type: 'integer')]
        private int $budgetDays,
        #[ORM\Column(name: 'budget_cents', type: 'integer')]
        private int $budgetCents,
        /** Lot parent (arborescence 2 niveaux) ; `null` pour un lot racine. */
        #[ORM\Column(name: 'parent_lot_id', type: 'guid', nullable: true)]
        private ?string $parentLotId = null,
    ) {
        if ('' === trim($name)) {
            throw new ProjectException('Le nom du lot est obligatoire.');
        }
        if ($budgetDays < 0 || $budgetCents < 0) {
            throw new ProjectException('Le budget d\'un lot ne peut pas être négatif.');
        }

        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
    }

    /** Réaffecte le budget (charge + montant) — la traçabilité (auteur/motif) relève du cas d'usage. */
    public function reallocateTo(int $budgetDays, int $budgetCents): void
    {
        if ($budgetDays < 0 || $budgetCents < 0) {
            throw new ProjectException('Le budget d\'un lot ne peut pas être négatif.');
        }
        $this->budgetDays = $budgetDays;
        $this->budgetCents = $budgetCents;
    }

    /**
     * Enregistre l'avancement physique (%) et le reste-à-faire (jours) — US-035. Les deux données sont
     * indépendantes (l'une sans l'autre est permis) et distinctes de la consommation valorisée (INV-4).
     */
    public function recordProgress(?int $physicalProgressPercent, ?int $remainingWorkDays): void
    {
        if (null !== $physicalProgressPercent && ($physicalProgressPercent < 0 || $physicalProgressPercent > 100)) {
            throw new ProjectException('L\'avancement physique doit être compris entre 0 et 100 %.');
        }
        if (null !== $remainingWorkDays && $remainingWorkDays < 0) {
            throw new ProjectException('Le reste-à-faire (RAF) ne peut pas être négatif.');
        }

        $this->physicalProgressPercent = $physicalProgressPercent;
        $this->remainingWorkDays = $remainingWorkDays;
    }

    public function physicalProgressPercent(): ?int
    {
        return $this->physicalProgressPercent;
    }

    public function remainingWorkDays(): ?int
    {
        return $this->remainingWorkDays;
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

    public function name(): string
    {
        return $this->name;
    }

    public function budgetDays(): int
    {
        return $this->budgetDays;
    }

    public function budgetCents(): int
    {
        return $this->budgetCents;
    }

    public function parentLotId(): ?string
    {
        return $this->parentLotId;
    }

    public function isRoot(): bool
    {
        return null === $this->parentLotId;
    }
}
