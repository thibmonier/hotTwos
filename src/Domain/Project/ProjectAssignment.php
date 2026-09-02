<?php

declare(strict_types=1);

namespace App\Domain\Project;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Affectation d'un collaborateur à un projet (US-037, EF-PRJ-19) : rôle, période et charge
 * prévisionnelle (jours). Seuls les collaborateurs affectés peuvent imputer sur le projet (CA-1) —
 * dès qu'une affectation existe, le projet devient restreint. La charge prévisionnelle alimentera le
 * plan de charge (EPIC-004, non livré). Portée par tenant.
 */
#[ORM\Entity]
#[ORM\Table(name: 'project_assignment')]
#[ORM\Index(name: 'idx_assignment_tenant_project', columns: ['tenant_id', 'project_id'])]
#[ORM\Index(name: 'idx_assignment_tenant_user', columns: ['tenant_id', 'user_id'])]
class ProjectAssignment implements TenantOwned
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
        #[ORM\Column(name: 'user_id', type: 'guid')]
        private string $userId,
        #[ORM\Column(name: 'role', length: 100)]
        private string $role,
        #[ORM\Column(name: 'planned_days', type: 'integer')]
        private int $plannedDays,
        #[ORM\Column(name: 'start_date', type: 'date_immutable', nullable: true)]
        private ?DateTimeImmutable $startDate = null,
        #[ORM\Column(name: 'end_date', type: 'date_immutable', nullable: true)]
        private ?DateTimeImmutable $endDate = null,
    ) {
        if ('' === trim($role)) {
            throw new ProjectException('Le rôle de l\'affectation est obligatoire.');
        }
        if ($plannedDays < 0) {
            throw new ProjectException('La charge prévisionnelle ne peut pas être négative.');
        }
        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
    }

    /** L'affectation couvre-t-elle ce jour (bornes incluses ; nulles = illimité) ? */
    public function coversDay(DateTimeImmutable $day): bool
    {
        $d = $day->format('Y-m-d');

        return (!$this->startDate instanceof DateTimeImmutable || $d >= $this->startDate->format('Y-m-d'))
            && (!$this->endDate instanceof DateTimeImmutable || $d <= $this->endDate->format('Y-m-d'));
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

    public function userId(): string
    {
        return $this->userId;
    }

    public function role(): string
    {
        return $this->role;
    }

    public function plannedDays(): int
    {
        return $this->plannedDays;
    }

    public function startDate(): ?DateTimeImmutable
    {
        return $this->startDate;
    }

    public function endDate(): ?DateTimeImmutable
    {
        return $this->endDate;
    }
}
