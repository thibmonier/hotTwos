<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Pricing\Profile;
use App\Domain\Pricing\ProfileRepository;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Implémentation Doctrine du {@see ProfileRepository} (US-011, DIP). Tenant explicite.
 */
final readonly class DoctrineProfileRepository implements ProfileRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(Profile $profile): void
    {
        $this->entityManager->persist($profile);
        $this->entityManager->flush();
    }

    public function find(TenantId $tenant, string $id): ?Profile
    {
        /** @var Profile|null $profile */
        $profile = $this->entityManager->createQuery(
            'SELECT p FROM '.Profile::class.' p WHERE p.tenantId = :tenant AND p.id = :id',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('id', $id)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $profile;
    }

    public function findByTenant(TenantId $tenant): array
    {
        /** @var list<Profile> $profiles */
        $profiles = $this->entityManager->createQuery(
            'SELECT p FROM '.Profile::class.' p WHERE p.tenantId = :tenant ORDER BY p.name ASC',
        )
            ->setParameter('tenant', $tenant->toString())
            ->getResult();

        return $profiles;
    }
}
