<?php

declare(strict_types=1);

namespace App\Domain\Organization;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

/**
 * Niveau hiérarchique nommé et paramétrable (US-010, EF-REF-1).
 *
 * Permet à l'admin de définir de 1 à N niveaux (« Direction », « Département », « Équipe »…)
 * sans développement : la {@see $position} ordonne les niveaux (1 = racine), le {@see $name}
 * les libelle. Portée par tenant, unicité (tenant, position) garantie en base.
 */
#[ORM\Entity]
#[ORM\Table(name: 'org_level_config')]
#[ORM\UniqueConstraint(name: 'uniq_org_level_tenant_position', columns: ['tenant_id', 'position'])]
class OrgLevelConfig implements TenantOwned
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    #[ORM\Column(type: 'integer')]
    private int $position;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    public function __construct(TenantId $tenantId, int $position, string $name)
    {
        if ($position < 1) {
            throw new InvalidArgumentException('La position d\'un niveau hiérarchique doit être un entier positif (1 = racine).');
        }

        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
        $this->position = $position;
        $this->name = $this->guardName($name);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }

    public function position(): int
    {
        return $this->position;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function rename(string $name): void
    {
        $this->name = $this->guardName($name);
    }

    private function guardName(string $name): string
    {
        $trimmed = trim($name);
        if ('' === $trimmed) {
            throw new InvalidArgumentException("Le nom d'un niveau hiérarchique est obligatoire.");
        }

        return $trimmed;
    }
}
