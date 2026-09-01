<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Pricing\ProfileRate;
use App\Domain\Pricing\ProfileRateRepository;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Implémentation Doctrine du {@see ProfileRateRepository} (US-011, DIP). Tenant explicite.
 */
final readonly class DoctrineProfileRateRepository implements ProfileRateRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(ProfileRate $rate): void
    {
        $this->entityManager->persist($rate);
        $this->entityManager->flush();
    }

    public function findForProfile(TenantId $tenant, string $profileId): array
    {
        /** @var list<ProfileRate> $rates */
        $rates = $this->entityManager->createQuery(
            'SELECT r FROM '.ProfileRate::class.' r'
            .' WHERE r.tenantId = :tenant AND r.profileId = :profile'
            .' ORDER BY r.effectiveFrom ASC',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('profile', $profileId)
            ->getResult();

        return $rates;
    }
}
