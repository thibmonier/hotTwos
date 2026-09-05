<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Budget\MarginDriftThreshold;
use App\Domain\Budget\MarginDriftThresholdRepository;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Implémentation Doctrine du {@see MarginDriftThresholdRepository} (US-018, DIP). Un seuil par tenant.
 */
final readonly class DoctrineMarginDriftThresholdRepository implements MarginDriftThresholdRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findForTenant(TenantId $tenant): ?MarginDriftThreshold
    {
        /** @var MarginDriftThreshold|null $threshold */
        $threshold = $this->entityManager->createQuery(
            'SELECT t FROM '.MarginDriftThreshold::class.' t WHERE t.tenantId = :tenant',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $threshold;
    }

    public function save(MarginDriftThreshold $threshold): void
    {
        $this->entityManager->persist($threshold);
        $this->entityManager->flush();
    }
}
