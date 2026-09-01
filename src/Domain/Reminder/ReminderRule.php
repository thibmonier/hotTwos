<?php

declare(strict_types=1);

namespace App\Domain\Reminder;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

/**
 * Règle de relance de retard de saisie d'un tenant (US-056, EF-TMP-21). **Une** règle par tenant.
 *
 * Paramètre le délai avant la première relance (à partir de l'échéance de complétude J+2 — US-058),
 * la fréquence des relances suivantes, le canal, l'escalade N+1 et l'activation globale. La borne
 * anti-spam (plancher 1 jour ouvré) n'est **pas** paramétrable ici : elle est appliquée par le moteur
 * quelle que soit la configuration. Portée par tenant (INV-1).
 */
#[ORM\Entity]
#[ORM\Table(name: 'reminder_rule')]
#[ORM\UniqueConstraint(name: 'uniq_reminder_rule_tenant', columns: ['tenant_id'])]
class ReminderRule implements TenantOwned
{
    /** Bornes défensives : au-delà, une relance n'a plus de sens fonctionnel. */
    private const int MAX_DELAY_DAYS = 30;
    private const int MAX_FREQUENCY_DAYS = 30;

    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    public function __construct(
        TenantId $tenantId,
        #[ORM\Column(name: 'initial_delay_days', type: 'smallint')]
        private int $initialDelayDays,
        #[ORM\Column(name: 'frequency_days', type: 'smallint')]
        private int $frequencyDays,
        #[ORM\Column(name: 'channel', length: 20, enumType: ReminderChannel::class)]
        private ReminderChannel $channel,
        #[ORM\Column(name: 'escalation_enabled', type: 'boolean')]
        private bool $escalationEnabled,
        #[ORM\Column(name: 'active', type: 'boolean')]
        private bool $active,
    ) {
        $this->guardDelays($initialDelayDays, $frequencyDays);
        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
    }

    /** Configuration de référence appliquée à l'initialisation d'un tenant : discrète et bornée. */
    public static function default(TenantId $tenantId): self
    {
        return new self($tenantId, 1, 3, ReminderChannel::IN_APP, true, true);
    }

    public function reconfigure(int $initialDelayDays, int $frequencyDays, ReminderChannel $channel, bool $escalationEnabled): void
    {
        $this->guardDelays($initialDelayDays, $frequencyDays);
        $this->initialDelayDays = $initialDelayDays;
        $this->frequencyDays = $frequencyDays;
        $this->channel = $channel;
        $this->escalationEnabled = $escalationEnabled;
    }

    public function activate(): void
    {
        $this->active = true;
    }

    public function deactivate(): void
    {
        $this->active = false;
    }

    private function guardDelays(int $initialDelayDays, int $frequencyDays): void
    {
        if ($initialDelayDays < 0 || $initialDelayDays > self::MAX_DELAY_DAYS) {
            throw new InvalidArgumentException(sprintf('Le délai initial doit être compris entre 0 et %d jours.', self::MAX_DELAY_DAYS));
        }
        if ($frequencyDays < 1 || $frequencyDays > self::MAX_FREQUENCY_DAYS) {
            throw new InvalidArgumentException(sprintf('La fréquence doit être comprise entre 1 et %d jours.', self::MAX_FREQUENCY_DAYS));
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

    public function initialDelayDays(): int
    {
        return $this->initialDelayDays;
    }

    public function frequencyDays(): int
    {
        return $this->frequencyDays;
    }

    public function channel(): ReminderChannel
    {
        return $this->channel;
    }

    public function escalationEnabled(): bool
    {
        return $this->escalationEnabled;
    }

    public function isActive(): bool
    {
        return $this->active;
    }
}
