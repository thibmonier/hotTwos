<?php

declare(strict_types=1);

namespace App\Domain\Absence;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

/**
 * Demande d'absence d'un collaborateur (US-054, EF-TMP-14/15).
 *
 * Maille **demi-journée** : la première journée peut commencer l'après-midi (`startsMorning = false`)
 * et la dernière se terminer le matin (`endsAfternoon = false`). Seules des données minimales sont
 * stockées — type normalisé, dates, commentaire libre optionnel — **jamais** de motif médical ni de
 * diagnostic (HAB-3, RGPD art. 9). Portée par tenant (INV-1).
 */
#[ORM\Entity]
#[ORM\Table(name: 'absence_request')]
#[ORM\Index(name: 'idx_absence_tenant_user', columns: ['tenant_id', 'user_id'])]
class AbsenceRequest implements TenantOwned
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    #[ORM\Column(name: 'status', length: 20, enumType: AbsenceStatus::class)]
    private AbsenceStatus $status;

    #[ORM\Column(name: 'decided_by', type: 'guid', nullable: true)]
    private ?string $decidedBy = null;

    #[ORM\Column(name: 'decided_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $decidedAt = null;

    #[ORM\Column(name: 'rejection_reason', type: 'text', nullable: true)]
    private ?string $rejectionReason = null;

    public function __construct(
        TenantId $tenantId,
        #[ORM\Column(name: 'user_id', type: 'guid')]
        private string $userId,
        #[ORM\Column(name: 'type_id', type: 'guid')]
        private string $typeId,
        #[ORM\Column(name: 'start_date', type: 'date_immutable')]
        private DateTimeImmutable $startDate,
        #[ORM\Column(name: 'end_date', type: 'date_immutable')]
        private DateTimeImmutable $endDate,
        #[ORM\Column(name: 'starts_morning', type: 'boolean')]
        private bool $startsMorning,
        #[ORM\Column(name: 'ends_afternoon', type: 'boolean')]
        private bool $endsAfternoon,
        #[ORM\Column(name: 'requested_at', type: 'datetime_immutable')]
        private DateTimeImmutable $requestedAt,
        #[ORM\Column(type: 'text', nullable: true)]
        private ?string $comment = null,
    ) {
        if ($startDate > $endDate) {
            throw new InvalidArgumentException('La date de début doit précéder ou égaler la date de fin.');
        }

        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
        $this->status = AbsenceStatus::PENDING;
        $this->comment = null === $comment || '' === trim($comment) ? null : trim($comment);

        if ($this->days() <= 0.0) {
            throw new InvalidArgumentException('La durée de l\'absence doit être strictement positive.');
        }
    }

    public function validate(string $validatorId, DateTimeImmutable $at): void
    {
        $this->status = AbsenceStatus::VALIDATED;
        $this->rejectionReason = null;
        $this->decidedBy = $validatorId;
        $this->decidedAt = $at;
    }

    public function reject(string $validatorId, string $reason, DateTimeImmutable $at): void
    {
        if ('' === trim($reason)) {
            throw new InvalidArgumentException('Un motif est obligatoire pour refuser une absence.');
        }

        $this->status = AbsenceStatus::REJECTED;
        $this->rejectionReason = trim($reason);
        $this->decidedBy = $validatorId;
        $this->decidedAt = $at;
    }

    /** Durée en jours (0,5 par demi-journée de bord). */
    public function days(): float
    {
        $days = (float) ((int) $this->startDate->diff($this->endDate)->days + 1);
        if (!$this->startsMorning) {
            $days -= 0.5;
        }
        if (!$this->endsAfternoon) {
            $days -= 0.5;
        }

        return $days;
    }

    /** Le jour donné est-il couvert par cette absence (bornes incluses) ? */
    public function coversDay(DateTimeImmutable $day): bool
    {
        $d = $day->format('Y-m-d');

        return $d >= $this->startDate->format('Y-m-d') && $d <= $this->endDate->format('Y-m-d');
    }

    public function id(): string
    {
        return $this->id;
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function typeId(): string
    {
        return $this->typeId;
    }

    public function status(): AbsenceStatus
    {
        return $this->status;
    }

    public function startDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function endDate(): DateTimeImmutable
    {
        return $this->endDate;
    }

    public function comment(): ?string
    {
        return $this->comment;
    }

    public function rejectionReason(): ?string
    {
        return $this->rejectionReason;
    }
}
