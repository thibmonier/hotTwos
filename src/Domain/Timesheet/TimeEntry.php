<?php

declare(strict_types=1);

namespace App\Domain\Timesheet;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Imputation de temps (US-050) : la durée d'un collaborateur sur un projet, un jour donné.
 * Portée par tenant (INV-1, TenantOwned).
 *
 * Durée en **minutes entières** (INV-2 — jamais de flottant sur une donnée de temps). Le
 * grain est (tenant, collaborateur, projet, jour) : une ligne par projet et par jour, dont
 * la durée est ajustée en cas de re-saisie.
 */
#[ORM\Entity]
#[ORM\Table(name: 'time_entry')]
#[ORM\UniqueConstraint(name: 'uniq_time_entry_grain', columns: ['tenant_id', 'user_id', 'project_id', 'work_date'])]
#[ORM\Index(name: 'idx_time_entry_tenant_user_date', columns: ['tenant_id', 'user_id', 'work_date'])]
class TimeEntry implements TenantOwned
{
    /** Plafond d'une imputation unitaire : 24 h (une journée). */
    public const int MAX_MINUTES_PER_ENTRY = 1440;

    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    #[ORM\Column(type: 'integer')]
    private int $minutes;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment;

    public function __construct(
        TenantId $tenantId,
        #[ORM\Column(name: 'user_id', type: 'guid')]
        private string $userId,
        #[ORM\Column(name: 'project_id', type: 'guid')]
        private string $projectId,
        #[ORM\Column(name: 'work_date', type: 'date_immutable')]
        private DateTimeImmutable $workDate,
        int $minutes,
        ?string $comment = null,
    ) {
        $this->guardMinutes($minutes);

        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
        $this->minutes = $minutes;
        $this->comment = $this->normalizeComment($comment);
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function projectId(): string
    {
        return $this->projectId;
    }

    public function workDate(): DateTimeImmutable
    {
        return $this->workDate;
    }

    public function minutes(): int
    {
        return $this->minutes;
    }

    public function comment(): ?string
    {
        return $this->comment;
    }

    /**
     * Réajuste la durée (re-saisie du même projet/jour) et le commentaire.
     */
    public function reviseTo(int $minutes, ?string $comment): void
    {
        $this->guardMinutes($minutes);
        $this->minutes = $minutes;
        $this->comment = $this->normalizeComment($comment);
    }

    private function guardMinutes(int $minutes): void
    {
        if ($minutes <= 0) {
            throw new InvalidArgumentException('La durée imputée doit être strictement positive.');
        }
        if ($minutes > self::MAX_MINUTES_PER_ENTRY) {
            throw new InvalidArgumentException(sprintf('La durée imputée ne peut pas dépasser %d minutes (24 h).', self::MAX_MINUTES_PER_ENTRY));
        }
    }

    private function normalizeComment(?string $comment): ?string
    {
        $trimmed = null === $comment ? null : trim($comment);

        return '' === $trimmed ? null : $trimmed;
    }
}
