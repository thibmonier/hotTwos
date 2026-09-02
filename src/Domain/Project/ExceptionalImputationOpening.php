<?php

declare(strict_types=1);

namespace App\Domain\Project;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Ouverture exceptionnelle d'imputation (US-037, CA-2) : autorise un collaborateur **non affecté** à
 * imputer sur un projet pour **une semaine** donnée, avec motif obligatoire et traçabilité. L'accès est
 * révoqué automatiquement à l'issue de la semaine (aucune imputation possible hors de cette semaine).
 * Portée par tenant.
 */
#[ORM\Entity]
#[ORM\Table(name: 'exceptional_imputation_opening')]
#[ORM\Index(name: 'idx_opening_tenant_project_user', columns: ['tenant_id', 'project_id', 'user_id'])]
class ExceptionalImputationOpening implements TenantOwned
{
    private const int MAX_REASON_LENGTH = 500;

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
        #[ORM\Column(name: 'week_start', type: 'date_immutable')]
        private DateTimeImmutable $weekStart,
        #[ORM\Column(name: 'reason', length: 500)]
        private string $reason,
        #[ORM\Column(name: 'granted_by', type: 'guid')]
        private string $grantedBy,
        #[ORM\Column(name: 'granted_at', type: 'datetime_immutable')]
        private DateTimeImmutable $grantedAt,
    ) {
        if ('' === trim($reason)) {
            throw new ProjectException('Un motif est obligatoire pour une ouverture exceptionnelle.');
        }
        if (mb_strlen($reason) > self::MAX_REASON_LENGTH) {
            throw new ProjectException('Le motif est trop long.');
        }
        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
        $this->weekStart = $weekStart->modify('monday this week')->setTime(0, 0);
        $this->reason = trim($reason);
    }

    /** L'ouverture couvre-t-elle ce jour (même semaine ISO que `weekStart`) ? */
    public function coversDay(DateTimeImmutable $day): bool
    {
        return $day->modify('monday this week')->format('Y-m-d') === $this->weekStart->format('Y-m-d');
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

    public function weekStart(): DateTimeImmutable
    {
        return $this->weekStart;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function grantedBy(): string
    {
        return $this->grantedBy;
    }

    public function grantedAt(): DateTimeImmutable
    {
        return $this->grantedAt;
    }
}
