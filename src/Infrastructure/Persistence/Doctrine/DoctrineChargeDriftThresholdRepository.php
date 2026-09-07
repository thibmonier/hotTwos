<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Budget\ChargeDriftThreshold;
use App\Domain\Budget\ChargeDriftThresholdRepository;
use App\Domain\Project\ContractType;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineChargeDriftThresholdRepository implements ChargeDriftThresholdRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findFor(TenantId $tenant, ContractType $type): ?ChargeDriftThreshold
    {
        $threshold = $this->entityManager->createQuery(
            'SELECT t FROM '.ChargeDriftThreshold::class.' t WHERE t.tenantId = :tenant AND t.contractType = :type',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('type', $type->value)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $threshold instanceof ChargeDriftThreshold ? $threshold : null;
    }

    public function findForTenant(TenantId $tenant): array
    {
        /** @var list<ChargeDriftThreshold> $thresholds */
        $thresholds = $this->entityManager->createQuery(
            'SELECT t FROM '.ChargeDriftThreshold::class.' t WHERE t.tenantId = :tenant ORDER BY t.contractType ASC',
        )
            ->setParameter('tenant', $tenant->toString())
            ->getResult();

        return $thresholds;
    }

    public function save(ChargeDriftThreshold $threshold): void
    {
        $this->entityManager->persist($threshold);
        $this->entityManager->flush();
    }
}
