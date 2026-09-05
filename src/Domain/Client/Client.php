<?php

declare(strict_types=1);

namespace App\Domain\Client;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use InvalidArgumentException;

/**
 * Compte client d'un tenant (US-014, tranche minimale — ADR-0022).
 *
 * Socle partagé projet/facturation : nom + SIREN optionnel. La hiérarchie groupe/filiale, les contacts
 * et la recherche avancée sont une tranche ultérieure. Portée par tenant (INV-1), nom unique par tenant.
 */
#[ORM\Entity]
#[ORM\Table(name: 'client')]
#[ORM\UniqueConstraint(name: 'uniq_client_tenant_name', columns: ['tenant_id', 'name'])]
#[ORM\Index(name: 'idx_client_tenant', columns: ['tenant_id'])]
class Client implements TenantOwned
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    public function __construct(
        TenantId $tenantId,
        #[ORM\Column(length: 255)]
        private string $name,
        #[ORM\Column(length: 9, nullable: true)]
        private ?string $siren = null,
    ) {
        $this->rename($name, $siren);
        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
    }

    public function rename(string $name, ?string $siren): void
    {
        if ('' === trim($name)) {
            throw new InvalidArgumentException('Le nom du client ne peut pas être vide.');
        }
        if (null !== $siren && 1 !== preg_match('/^\d{9}$/', $siren)) {
            throw new InvalidArgumentException('Le SIREN, s\'il est renseigné, doit comporter 9 chiffres.');
        }
        $this->name = trim($name);
        $this->siren = $siren;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function siren(): ?string
    {
        return $this->siren;
    }
}
