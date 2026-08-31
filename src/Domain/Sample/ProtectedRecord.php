<?php

declare(strict_types=1);

namespace App\Domain\Sample;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Sonde de validation de l'isolation multi-tenant (ENF-SEC-4).
 *
 * Entité minimale portée par tenant ({@see TenantOwned}), servant à prouver que le
 * filtre d'isolation (ARC-33) et la RLS (ARC-34) cloisonnent réellement les données.
 * À remplacer par les entités métier réelles du lot 1 (organisation, projet, temps…).
 */
#[ORM\Entity]
#[ORM\Table(name: 'protected_record')]
class ProtectedRecord implements TenantOwned
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    public function __construct(TenantId $tenantId, #[ORM\Column(length: 255)]
        private string $label)
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
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
