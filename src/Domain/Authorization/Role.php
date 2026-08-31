<?php

declare(strict_types=1);

namespace App\Domain\Authorization;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use InvalidArgumentException;

/**
 * Rôle applicatif porté par un tenant (US-003, INV-1) : un nom, un ensemble de
 * permissions fonctionnelles et un périmètre de données ({@see DataScope}).
 *
 * Le rôle est la brique paramétrable de la matrice reproductible (CA-4). Le contrôle
 * d'accès effectif est assuré par la couche applicative ({@see \App\Application\Authorization\Authorizer}),
 * jamais par l'UI (ARC-19, ARC-106).
 */
#[ORM\Entity]
#[ORM\Table(name: 'auth_role')]
#[ORM\UniqueConstraint(name: 'uniq_role_tenant_name', columns: ['tenant_id', 'name'])]
class Role implements TenantOwned
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    #[ORM\Column(length: 100)]
    private string $name;

    /**
     * Permissions stockées par leur valeur (`string`) — hydratées en {@see Permission}
     * à la lecture pour ne jamais exposer de chaîne libre au domaine.
     *
     * @var list<string>
     */
    #[ORM\Column(type: 'json')]
    private array $permissions;

    /**
     * @param list<Permission> $permissions
     */
    public function __construct(TenantId $tenantId, string $name, array $permissions, #[ORM\Column(name: 'data_scope', length: 20, enumType: DataScope::class)]
        private DataScope $scope)
    {
        if ('' === $name) {
            throw new InvalidArgumentException('Le nom du rôle ne peut pas être vide.');
        }

        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
        $this->name = $name;
        $this->permissions = array_values(array_unique(array_map(
            static fn (Permission $permission): string => $permission->value,
            $permissions,
        )));
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function scope(): DataScope
    {
        return $this->scope;
    }

    public function grants(Permission $permission): bool
    {
        return in_array($permission->value, $this->permissions, true);
    }

    /**
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return array_map(
            Permission::from(...),
            $this->permissions,
        );
    }

    /**
     * Réaligne un rôle existant sur la matrice de référence (CA-4) sans changer son
     * identité — support de la ré-application idempotente de la matrice par défaut.
     *
     * @param list<Permission> $permissions
     */
    public function realignTo(array $permissions, DataScope $scope): void
    {
        $this->permissions = array_values(array_unique(array_map(
            static fn (Permission $permission): string => $permission->value,
            $permissions,
        )));
        $this->scope = $scope;
    }
}
