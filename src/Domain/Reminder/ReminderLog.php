<?php

declare(strict_types=1);

namespace App\Domain\Reminder;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

/**
 * Trace d'une relance effectivement émise (US-056, journalisation). Alimente l'historique filtrable
 * et sert de mémoire au moteur : le rang ({@see sequence}) pilote l'escalade N+1 et le plancher
 * anti-spam se calcule à partir de la dernière trace `(collaborateur, semaine)`. Portée par tenant.
 */
#[ORM\Entity]
#[ORM\Table(name: 'reminder_log')]
#[ORM\Index(name: 'idx_reminder_log_tenant_user_week', columns: ['tenant_id', 'user_id', 'week_start'])]
#[ORM\Index(name: 'idx_reminder_log_tenant_sent', columns: ['tenant_id', 'sent_at'])]
class ReminderLog implements TenantOwned
{
    private const int MAX_REASON_LENGTH = 100;

    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    public function __construct(
        TenantId $tenantId,
        #[ORM\Column(name: 'user_id', type: 'guid')]
        private string $userId,
        #[ORM\Column(name: 'week_start', type: 'date_immutable')]
        private DateTimeImmutable $weekStart,
        #[ORM\Column(name: 'channel', length: 20, enumType: ReminderChannel::class)]
        private ReminderChannel $channel,
        #[ORM\Column(name: 'sequence_no', type: 'smallint')]
        private int $sequence,
        #[ORM\Column(name: 'escalated', type: 'boolean')]
        private bool $escalated,
        #[ORM\Column(name: 'sent_at', type: 'datetime_immutable')]
        private DateTimeImmutable $sentAt,
        #[ORM\Column(name: 'reason', length: 100, nullable: true)]
        private ?string $reason = null,
    ) {
        if ($sequence < 1) {
            throw new InvalidArgumentException('Le rang d\'une relance est au minimum 1.');
        }
        if (null !== $reason && mb_strlen($reason) > self::MAX_REASON_LENGTH) {
            throw new InvalidArgumentException('Le motif de relance est trop long.');
        }

        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
        $this->reason = null === $reason || '' === trim($reason) ? null : trim($reason);
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

    public function weekStart(): DateTimeImmutable
    {
        return $this->weekStart;
    }

    public function channel(): ReminderChannel
    {
        return $this->channel;
    }

    public function sequence(): int
    {
        return $this->sequence;
    }

    public function isEscalated(): bool
    {
        return $this->escalated;
    }

    public function sentAt(): DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function reason(): ?string
    {
        return $this->reason;
    }
}
