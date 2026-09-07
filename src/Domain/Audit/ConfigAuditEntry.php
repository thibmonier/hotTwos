<?php

declare(strict_types=1);

namespace App\Domain\Audit;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * US-020 (EF-REF-33, INV-7) — entrée immuable du journal d'audit du paramétrage : qui, quoi, valeur
 * avant/après, quand. Append-only : aucune méthode de modification.
 */
#[ORM\Entity]
#[ORM\Table(name: 'config_audit_entry')]
#[ORM\Index(name: 'idx_config_audit_tenant_recorded', columns: ['tenant_id', 'recorded_at'])]
class ConfigAuditEntry implements TenantOwned
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    public function __construct(
        TenantId $tenantId,
        #[ORM\Column(name: 'actor_user_id', type: 'guid')]
        private string $actorUserId,
        #[ORM\Column(name: 'action', length: 20, enumType: AuditAction::class)]
        private AuditAction $action,
        #[ORM\Column(name: 'object_type', length: 100)]
        private string $objectType,
        #[ORM\Column(name: 'object_label', length: 255)]
        private string $objectLabel,
        #[ORM\Column(name: 'field', length: 100, nullable: true)]
        private ?string $field,
        #[ORM\Column(name: 'value_before', length: 255, nullable: true)]
        private ?string $valueBefore,
        #[ORM\Column(name: 'value_after', length: 255, nullable: true)]
        private ?string $valueAfter,
        #[ORM\Column(name: 'recorded_at', type: 'datetime_immutable')]
        private DateTimeImmutable $recordedAt,
    ) {
        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }

    public function actorUserId(): string
    {
        return $this->actorUserId;
    }

    public function action(): AuditAction
    {
        return $this->action;
    }

    public function objectType(): string
    {
        return $this->objectType;
    }

    public function objectLabel(): string
    {
        return $this->objectLabel;
    }

    public function field(): ?string
    {
        return $this->field;
    }

    public function valueBefore(): ?string
    {
        return $this->valueBefore;
    }

    public function valueAfter(): ?string
    {
        return $this->valueAfter;
    }

    public function recordedAt(): DateTimeImmutable
    {
        return $this->recordedAt;
    }
}
