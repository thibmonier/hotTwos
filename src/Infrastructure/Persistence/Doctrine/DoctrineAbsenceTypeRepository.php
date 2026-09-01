<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Absence\AbsenceType;
use App\Domain\Absence\AbsenceTypeRepository;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Implémentation Doctrine du {@see AbsenceTypeRepository} (US-054, DIP). Tenant explicite.
 */
final readonly class DoctrineAbsenceTypeRepository implements AbsenceTypeRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(AbsenceType $type): void
    {
        $this->entityManager->persist($type);
        $this->entityManager->flush();
    }

    public function findById(TenantId $tenant, string $id): ?AbsenceType
    {
        /** @var AbsenceType|null $found */
        $found = $this->entityManager->createQuery(
            'SELECT t FROM '.AbsenceType::class.' t WHERE t.tenantId = :tenant AND t.id = :id',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('id', $id)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $found;
    }

    public function findAllByTenant(TenantId $tenant): array
    {
        /** @var list<AbsenceType> $types */
        $types = $this->entityManager->createQuery(
            'SELECT t FROM '.AbsenceType::class.' t WHERE t.tenantId = :tenant ORDER BY t.label ASC',
        )->setParameter('tenant', $tenant->toString())->getResult();

        return $types;
    }
}
