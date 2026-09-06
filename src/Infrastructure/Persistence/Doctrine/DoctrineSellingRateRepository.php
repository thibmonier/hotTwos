<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Pricing\RateScope;
use App\Domain\Pricing\SellingRate;
use App\Domain\Pricing\SellingRateRepository;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineSellingRateRepository implements SellingRateRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(SellingRate $rate): void
    {
        $this->entityManager->persist($rate);
        $this->entityManager->flush();
    }

    public function findForScope(TenantId $tenant, string $profileId, RateScope $scope, string $scopeRefId): array
    {
        /** @var list<SellingRate> $rows */
        $rows = $this->entityManager->createQuery(
            'SELECT r FROM '.SellingRate::class.' r '
            .'WHERE r.tenantId = :tenant AND r.profileId = :profile AND r.scope = :scope AND r.scopeRefId = :ref '
            .'ORDER BY r.effectiveFrom ASC',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('profile', $profileId)
            ->setParameter('scope', $scope->value)
            ->setParameter('ref', $scopeRefId)
            ->getResult();

        return $rows;
    }
}
