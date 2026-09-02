<?php

declare(strict_types=1);

namespace App\Domain\Project;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Réouverture exceptionnelle d'un projet clôturé (US-038, CA-3). Demandée par un chef de projet,
 * **approuvée par un ADMIN distinct** (4-eyes), bornée par une fenêtre (`openUntil`) au-delà de
 * laquelle la clôture reprend automatiquement. Traçée durablement. Portée par tenant.
 */
#[ORM\Entity]
#[ORM\Table(name: 'project_reopening')]
#[ORM\Index(name: 'idx_reopening_tenant_project', columns: ['tenant_id', 'project_id'])]
class ProjectReopening implements TenantOwned
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    #[ORM\Column(name: 'approved_by', type: 'guid', nullable: true)]
    private ?string $approvedBy = null;

    #[ORM\Column(name: 'approved_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $approvedAt = null;

    #[ORM\Column(name: 'open_until', type: 'date_immutable', nullable: true)]
    private ?DateTimeImmutable $openUntil = null;

    public function __construct(
        TenantId $tenantId,
        #[ORM\Column(name: 'project_id', type: 'guid')]
        private string $projectId,
        #[ORM\Column(name: 'requested_by', type: 'guid')]
        private string $requestedBy,
        #[ORM\Column(name: 'reason', length: 500)]
        private string $reason,
        #[ORM\Column(name: 'requested_at', type: 'datetime_immutable')]
        private DateTimeImmutable $requestedAt,
    ) {
        if ('' === trim($reason)) {
            throw new ProjectException('Un motif est obligatoire pour une réouverture.');
        }
        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
        $this->reason = trim($reason);
    }

    /** Approbation par un ADMIN **distinct** du demandeur (4-eyes) ; ouvre la fenêtre jusqu'à `openUntil`. */
    public function approve(string $approverId, DateTimeImmutable $at, DateTimeImmutable $openUntil): void
    {
        if ($approverId === $this->requestedBy) {
            throw new ProjectException('La réouverture doit être approuvée par une personne distincte du demandeur (4-eyes).');
        }
        if ($this->approvedAt instanceof DateTimeImmutable) {
            throw new ProjectException('Cette réouverture a déjà été approuvée.');
        }
        $this->approvedBy = $approverId;
        $this->approvedAt = $at;
        $this->openUntil = $openUntil->setTime(0, 0);
    }

    public function isActiveOn(DateTimeImmutable $day): bool
    {
        return $this->approvedAt instanceof DateTimeImmutable && $this->openUntil instanceof DateTimeImmutable
            && $day->format('Y-m-d') <= $this->openUntil->format('Y-m-d');
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

    public function requestedBy(): string
    {
        return $this->requestedBy;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function isApproved(): bool
    {
        return $this->approvedAt instanceof DateTimeImmutable;
    }

    public function openUntil(): ?DateTimeImmutable
    {
        return $this->openUntil;
    }
}
