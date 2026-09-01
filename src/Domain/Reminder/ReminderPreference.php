<?php

declare(strict_types=1);

namespace App\Domain\Reminder;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Préférence individuelle de relance d'un collaborateur (US-056, CA-2, RGPD).
 *
 * L'opt-out est un **droit du collaborateur** : l'administrateur peut désactiver globalement les
 * relances du tenant ({@see ReminderRule::deactivate()}) mais **ne peut pas** forcer la réactivation
 * d'un opt-out individuel — aucune API ne l'y autorise (CA-2). Portée par tenant (INV-1).
 */
#[ORM\Entity]
#[ORM\Table(name: 'reminder_preference')]
#[ORM\UniqueConstraint(name: 'uniq_reminder_pref_tenant_user', columns: ['tenant_id', 'user_id'])]
class ReminderPreference implements TenantOwned
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    public function __construct(
        TenantId $tenantId,
        #[ORM\Column(name: 'user_id', type: 'guid')]
        private string $userId,
        #[ORM\Column(name: 'opted_out', type: 'boolean')]
        private bool $optedOut,
        #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
        private DateTimeImmutable $updatedAt,
    ) {
        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
    }

    public function optOut(DateTimeImmutable $at): void
    {
        $this->optedOut = true;
        $this->updatedAt = $at;
    }

    public function optIn(DateTimeImmutable $at): void
    {
        $this->optedOut = false;
        $this->updatedAt = $at;
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

    public function isOptedOut(): bool
    {
        return $this->optedOut;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
