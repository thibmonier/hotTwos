<?php

declare(strict_types=1);

namespace App\Domain\Absence;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

/**
 * Type d'absence du référentiel tenant (US-054, EF-TMP-14) : libellé **normalisé** uniquement
 * (« Congés payés », « Arrêt maladie »…). Jamais de motif médical ni de diagnostic (HAB-3).
 * Portée par tenant (INV-1).
 */
#[ORM\Entity]
#[ORM\Table(name: 'absence_type')]
#[ORM\UniqueConstraint(name: 'uniq_absence_type_tenant_label', columns: ['tenant_id', 'label'])]
class AbsenceType implements TenantOwned
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    public function __construct(
        TenantId $tenantId,
        #[ORM\Column(length: 100)]
        private string $label,
    ) {
        if ('' === trim($label)) {
            throw new InvalidArgumentException('Le libellé du type d\'absence ne peut pas être vide.');
        }

        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
        $this->label = trim($label);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }

    public function label(): string
    {
        return $this->label;
    }
}
