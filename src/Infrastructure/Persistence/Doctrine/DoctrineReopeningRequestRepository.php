<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Period\ReopeningRequest;
use App\Domain\Period\ReopeningRequestRepository;
use App\Domain\Period\ReopeningStatus;
use App\Domain\Tenant\TenantId;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Implémentation Doctrine du {@see ReopeningRequestRepository} (US-057, DIP). Tenant explicite.
 */
final readonly class DoctrineReopeningRequestRepository implements ReopeningRequestRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(ReopeningRequest $request): void
    {
        $this->entityManager->persist($request);
        $this->entityManager->flush();
    }

    public function findById(TenantId $tenant, string $id): ?ReopeningRequest
    {
        /** @var ReopeningRequest|null $found */
        $found = $this->entityManager->createQuery(
            'SELECT r FROM '.ReopeningRequest::class.' r WHERE r.tenantId = :tenant AND r.id = :id',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('id', $id)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $found;
    }

    public function findActiveForPeriod(TenantId $tenant, string $period, DateTimeImmutable $now): ?ReopeningRequest
    {
        /** @var ReopeningRequest|null $found */
        $found = $this->entityManager->createQuery(
            'SELECT r FROM '.ReopeningRequest::class.' r'
            .' WHERE r.tenantId = :tenant AND r.period = :period AND r.status = :approved AND r.validUntil > :now'
            .' ORDER BY r.validUntil DESC',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('period', $period)
            ->setParameter('approved', ReopeningStatus::APPROVED->value)
            ->setParameter('now', $now, 'datetime_immutable')
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $found;
    }
}
