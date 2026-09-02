<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Domain\User\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Implémentation Doctrine du {@see UserRepository} (DIP). Cloisonnement par tenant explicite.
 */
final readonly class DoctrineUserRepository implements UserRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function existsInTenant(TenantId $tenant, string $userId): bool
    {
        $count = $this->entityManager->createQuery(
            'SELECT COUNT(u.id) FROM '.User::class.' u WHERE u.tenantId = :tenant AND u.id = :id',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('id', $userId)
            ->getSingleScalarResult();

        return (int) $count > 0;
    }

    public function findIdsByTenant(TenantId $tenant): array
    {
        /** @var list<array{id: string}> $rows */
        $rows = $this->entityManager->createQuery(
            'SELECT u.id AS id FROM '.User::class.' u WHERE u.tenantId = :tenant ORDER BY u.email ASC',
        )->setParameter('tenant', $tenant->toString())->getResult();

        return array_map(static fn (array $row): string => $row['id'], $rows);
    }

    public function findEmailsByIds(TenantId $tenant, array $userIds): array
    {
        if ([] === $userIds) {
            return [];
        }

        /** @var list<array{id: string, email: string}> $rows */
        $rows = $this->entityManager->createQuery(
            'SELECT u.id AS id, u.email AS email FROM '.User::class.' u WHERE u.tenantId = :tenant AND u.id IN (:ids)',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('ids', $userIds)
            ->getResult();

        $emails = [];
        foreach ($rows as $row) {
            $emails[$row['id']] = $row['email'];
        }

        return $emails;
    }
}
