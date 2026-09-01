<?php

declare(strict_types=1);

namespace App\Domain\Period;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Demande de réouverture formelle d'une période clôturée (US-057, CA-2, RG-TMP-6).
 *
 * Tracée de bout en bout : demandeur, motif, approbateur, date de validité. Une réouverture
 * **approuvée** ouvre une fenêtre de modification bornée (`validUntil`) : au-delà, la période est
 * de nouveau verrouillée (reclôture automatique passive). Portée par tenant (INV-1).
 */
#[ORM\Entity]
#[ORM\Table(name: 'period_reopening_request')]
#[ORM\Index(name: 'idx_reopening_tenant_period', columns: ['tenant_id', 'period'])]
class ReopeningRequest implements TenantOwned
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    #[ORM\Column(name: 'approved_by', type: 'guid', nullable: true)]
    private ?string $approvedBy = null;

    #[ORM\Column(name: 'valid_until', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $validUntil = null;

    public function __construct(
        TenantId $tenantId,
        #[ORM\Column(length: 7)]
        private string $period,
        #[ORM\Column(name: 'requested_by', type: 'guid')]
        private string $requestedBy,
        #[ORM\Column(type: 'text')]
        private string $reason,
        #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
        private DateTimeImmutable $createdAt,
        #[ORM\Column(name: 'status', length: 20, enumType: ReopeningStatus::class)]
        private ReopeningStatus $status = ReopeningStatus::REQUESTED,
    ) {
        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
    }

    public function approve(string $approverId, DateTimeImmutable $validUntil): void
    {
        $this->status = ReopeningStatus::APPROVED;
        $this->approvedBy = $approverId;
        $this->validUntil = $validUntil;
    }

    public function reject(string $approverId): void
    {
        $this->status = ReopeningStatus::REJECTED;
        $this->approvedBy = $approverId;
    }

    /**
     * Fenêtre de modification ouverte : demande approuvée et non encore expirée.
     */
    public function isActiveAt(DateTimeImmutable $now): bool
    {
        return ReopeningStatus::APPROVED === $this->status
            && $this->validUntil instanceof DateTimeImmutable
            && $this->validUntil > $now;
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

    public function status(): ReopeningStatus
    {
        return $this->status;
    }

    public function validUntil(): ?DateTimeImmutable
    {
        return $this->validUntil;
    }
}
