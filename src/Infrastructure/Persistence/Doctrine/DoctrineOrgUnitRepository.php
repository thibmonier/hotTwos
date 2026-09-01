<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Organization\OrgUnit;
use App\Domain\Organization\OrgUnitRepository;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Implémentation Doctrine du {@see OrgUnitRepository} (US-010, DIP). Tenant explicite.
 */
final readonly class DoctrineOrgUnitRepository implements OrgUnitRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(OrgUnit $unit): void
    {
        $this->entityManager->persist($unit);
        $this->entityManager->flush();
    }

    public function find(TenantId $tenant, string $id): ?OrgUnit
    {
        /** @var OrgUnit|null $unit */
        $unit = $this->entityManager->createQuery(
            'SELECT u FROM '.OrgUnit::class.' u WHERE u.tenantId = :tenant AND u.id = :id',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('id', $id)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $unit;
    }

    public function findByTenant(TenantId $tenant): array
    {
        /** @var list<OrgUnit> $units */
        $units = $this->entityManager->createQuery(
            'SELECT u FROM '.OrgUnit::class.' u WHERE u.tenantId = :tenant ORDER BY u.name ASC',
        )
            ->setParameter('tenant', $tenant->toString())
            ->getResult();

        return $units;
    }
}
