<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Authorization\Role;
use App\Domain\Authorization\RoleRepository;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Implémentation Doctrine du {@see RoleRepository} (ARC-18, DIP).
 *
 * Le cloisonnement par tenant est exprimé explicitement dans chaque requête (le repo
 * reçoit le `TenantId`), ce qui le rend correct hors requête HTTP — notamment pour la
 * commande d'initialisation en CLI, où le filtre applicatif n'est pas activé. Le filtre
 * global (US-001) reste une défense en profondeur pour les autres accès.
 */
final readonly class DoctrineRoleRepository implements RoleRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findByNames(TenantId $tenant, array $names): array
    {
        if ([] === $names) {
            return [];
        }

        /** @var list<Role> $roles */
        $roles = $this->entityManager->createQuery(
            'SELECT r FROM '.Role::class.' r WHERE r.tenantId = :tenant AND r.name IN (:names)',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('names', $names)
            ->getResult();

        return $roles;
    }

    public function findByName(TenantId $tenant, string $name): ?Role
    {
        /** @var Role|null $role */
        $role = $this->entityManager->createQuery(
            'SELECT r FROM '.Role::class.' r WHERE r.tenantId = :tenant AND r.name = :name',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('name', $name)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $role;
    }

    public function save(Role $role): void
    {
        $this->entityManager->persist($role);
        $this->entityManager->flush();
    }
}
