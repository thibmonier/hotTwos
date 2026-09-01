<?php

declare(strict_types=1);

namespace App\Domain\Organization;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

/**
 * Unité organisationnelle (US-010, EF-REF-1/3) : un nœud de la hiérarchie du tenant.
 *
 * La profondeur est libre (1..N niveaux) : elle se lit par la chaîne des parents, les niveaux
 * étant nommés par {@see OrgLevelConfig}. Une unité racine (parent nul) peut représenter une
 * entité juridique (EF-REF-3). Portée par tenant (INV-1). Jamais supprimée si elle est
 * référencée : on la **désactive** (RG-REF-1, appliqué en couche applicative).
 */
#[ORM\Entity]
#[ORM\Table(name: 'org_unit')]
#[ORM\Index(name: 'idx_org_unit_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_org_unit_tenant_parent', columns: ['tenant_id', 'parent_id'])]
class OrgUnit implements TenantOwned
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'boolean')]
    private bool $active;

    public function __construct(TenantId $tenantId, #[ORM\Column(name: 'parent_id', type: 'guid', nullable: true)]
        private ?string $parentId, string $name)
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
        $this->name = $this->guardName($name);
        $this->active = true;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }

    public function parentId(): ?string
    {
        return $this->parentId;
    }

    public function isRoot(): bool
    {
        return null === $this->parentId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function rename(string $name): void
    {
        $this->name = $this->guardName($name);
    }

    /**
     * Rattache l'unité à un nouveau parent (ou la promeut racine si null). La prévention des
     * cycles est assurée en couche applicative, qui connaît l'ensemble de la hiérarchie.
     */
    public function attachToParent(?string $parentId): void
    {
        $this->parentId = $parentId;
    }

    public function deactivate(): void
    {
        $this->active = false;
    }

    public function activate(): void
    {
        $this->active = true;
    }

    private function guardName(string $name): string
    {
        $trimmed = trim($name);
        if ('' === $trimmed) {
            throw new InvalidArgumentException("Le nom d'une unité organisationnelle est obligatoire.");
        }
        if (mb_strlen($trimmed) > 255) {
            throw new InvalidArgumentException("Le nom d'une unité organisationnelle ne peut pas dépasser 255 caractères.");
        }

        return $trimmed;
    }
}
