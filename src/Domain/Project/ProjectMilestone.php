<?php

declare(strict_types=1);

namespace App\Domain\Project;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Jalon d'un projet (US-031, EF-PRJ-3) : date prévisionnelle, date réelle d'atteinte, statut, et
 * **déclencheur de facturation** optionnel. L'émission de facture relève d'EPIC-005 (non livré) :
 * ici, l'atteinte d'un jalon déclencheur **enregistre l'intention** de façon **idempotente** (une
 * seule fois — CA-7). Portée par tenant.
 */
#[ORM\Entity]
#[ORM\Table(name: 'project_milestone')]
#[ORM\Index(name: 'idx_milestone_tenant_project', columns: ['tenant_id', 'project_id'])]
class ProjectMilestone implements TenantOwned
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    #[ORM\Column(name: 'status', length: 20, enumType: MilestoneStatus::class)]
    private MilestoneStatus $status;

    #[ORM\Column(name: 'reached_date', type: 'date_immutable', nullable: true)]
    private ?DateTimeImmutable $reachedDate = null;

    #[ORM\Column(name: 'billing_triggered_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $billingTriggeredAt = null;

    public function __construct(
        TenantId $tenantId,
        #[ORM\Column(name: 'project_id', type: 'guid')]
        private string $projectId,
        #[ORM\Column(name: 'name', length: 150)]
        private string $name,
        #[ORM\Column(name: 'due_date', type: 'date_immutable')]
        private DateTimeImmutable $dueDate,
        /** Montant € HT à facturer à l'atteinte (déclencheur optionnel). */
        #[ORM\Column(name: 'billing_trigger_cents', type: 'integer', nullable: true)]
        private ?int $billingTriggerCents = null,
    ) {
        if ('' === trim($name)) {
            throw new ProjectException('Le nom du jalon est obligatoire.');
        }
        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
        $this->status = MilestoneStatus::A_VENIR;
    }

    /**
     * Marque le jalon atteint. Si un déclencheur de facturation est défini, enregistre l'intention
     * **une seule fois** : une seconde atteinte d'un jalon déjà facturé est refusée (CA-7).
     */
    public function markReached(DateTimeImmutable $at): void
    {
        if (null !== $this->billingTriggerCents && $this->billingTriggeredAt instanceof DateTimeImmutable) {
            throw new ProjectException(sprintf('Ce jalon a déjà déclenché une facturation le %s. Nouvelle facturation non autorisée (EF-PRJ-3).', $this->billingTriggeredAt->format('d/m/Y')));
        }

        $this->status = MilestoneStatus::ATTEINT;
        $this->reachedDate = $at;
        if (null !== $this->billingTriggerCents) {
            $this->billingTriggeredAt = $at;
        }
    }

    public function markDelayed(): void
    {
        if (MilestoneStatus::ATTEINT !== $this->status) {
            $this->status = MilestoneStatus::RETARDE;
        }
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

    public function dueDate(): DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function reachedDate(): ?DateTimeImmutable
    {
        return $this->reachedDate;
    }

    public function status(): MilestoneStatus
    {
        return $this->status;
    }

    public function billingTriggerCents(): ?int
    {
        return $this->billingTriggerCents;
    }

    public function hasBillingTrigger(): bool
    {
        return null !== $this->billingTriggerCents;
    }

    public function billingTriggeredAt(): ?DateTimeImmutable
    {
        return $this->billingTriggeredAt;
    }
}
