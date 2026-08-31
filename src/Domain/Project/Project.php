<?php

declare(strict_types=1);

namespace App\Domain\Project;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use InvalidArgumentException;

/**
 * Projet — référentiel **minimal** (US-050) : de quoi imputer du temps dessus. Porté par
 * tenant (INV-1, TenantOwned).
 *
 * Volontairement dégénéré au Sprint 3 : identité (code, nom) et état actif. La structure
 * riche (lots, jalons, budgets — US-030/031/033) et l'affectation (US-037) sont ultérieures.
 */
#[ORM\Entity]
#[ORM\Table(name: 'project')]
#[ORM\UniqueConstraint(name: 'uniq_project_tenant_code', columns: ['tenant_id', 'code'])]
#[ORM\Index(name: 'idx_project_tenant', columns: ['tenant_id'])]
class Project implements TenantOwned
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    #[ORM\Column(length: 50)]
    private string $code;

    #[ORM\Column(length: 255)]
    private string $name;

    public function __construct(TenantId $tenantId, string $code, string $name, #[ORM\Column(type: 'boolean')]
        private bool $active = true)
    {
        if ('' === $code) {
            throw new InvalidArgumentException('Le code projet ne peut pas être vide.');
        }
        if ('' === $name) {
            throw new InvalidArgumentException('Le nom du projet ne peut pas être vide.');
        }

        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
        $this->code = $code;
        $this->name = $name;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }

    public function code(): string
    {
        return $this->code;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isActive(): bool
    {
        return $this->active;
    }
}
