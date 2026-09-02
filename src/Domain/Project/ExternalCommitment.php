<?php

declare(strict_types=1);

namespace App\Domain\Project;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Engagement externe rattaché à un projet (US-034, EF-PRJ-10) : sous-traitance, achats, licences…
 * Montant en € HT, fournisseur, statut, éventuellement rattaché à un lot. Inclus dans le calcul de
 * **marge** (coûts externes) mais pas dans la charge (jours). Portée par tenant.
 */
#[ORM\Entity]
#[ORM\Table(name: 'project_external_commitment')]
#[ORM\Index(name: 'idx_commitment_tenant_project', columns: ['tenant_id', 'project_id'])]
class ExternalCommitment implements TenantOwned
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    public function __construct(
        TenantId $tenantId,
        #[ORM\Column(name: 'project_id', type: 'guid')]
        private string $projectId,
        #[ORM\Column(name: 'type', length: 20, enumType: CommitmentType::class)]
        private CommitmentType $type,
        #[ORM\Column(name: 'label', length: 200)]
        private string $label,
        #[ORM\Column(name: 'amount_cents', type: 'integer')]
        private int $amountCents,
        #[ORM\Column(name: 'supplier', length: 200)]
        private string $supplier,
        #[ORM\Column(name: 'status', length: 20, enumType: CommitmentStatus::class)]
        private CommitmentStatus $status,
        #[ORM\Column(name: 'lot_id', type: 'guid', nullable: true)]
        private ?string $lotId = null,
    ) {
        if ($amountCents <= 0) {
            throw new ProjectException('Le montant de l\'engagement est obligatoire.');
        }
        if ('' === trim($supplier)) {
            throw new ProjectException('Le fournisseur est obligatoire.');
        }
        if ('' === trim($label)) {
            throw new ProjectException('Le libellé de l\'engagement est obligatoire.');
        }
        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
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

    public function type(): CommitmentType
    {
        return $this->type;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function amountCents(): int
    {
        return $this->amountCents;
    }

    public function supplier(): string
    {
        return $this->supplier;
    }

    public function status(): CommitmentStatus
    {
        return $this->status;
    }

    public function lotId(): ?string
    {
        return $this->lotId;
    }
}
