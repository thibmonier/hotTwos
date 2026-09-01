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
}
