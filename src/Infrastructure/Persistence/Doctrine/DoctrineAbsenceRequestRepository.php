<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Absence\AbsenceRequest;
use App\Domain\Absence\AbsenceRequestRepository;
use App\Domain\Absence\AbsenceStatus;
use App\Domain\Tenant\TenantId;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Implémentation Doctrine du {@see AbsenceRequestRepository} (US-054, DIP). Tenant explicite.
 */
final readonly class DoctrineAbsenceRequestRepository implements AbsenceRequestRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(AbsenceRequest $request): void
    {
        $this->entityManager->persist($request);
        $this->entityManager->flush();
    }

    public function findById(TenantId $tenant, string $id): ?AbsenceRequest
    {
        /** @var AbsenceRequest|null $found */
        $found = $this->entityManager->createQuery(
            'SELECT r FROM '.AbsenceRequest::class.' r WHERE r.tenantId = :tenant AND r.id = :id',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('id', $id)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $found;
    }

    public function findForUser(TenantId $tenant, string $userId): array
    {
        /** @var list<AbsenceRequest> $requests */
        $requests = $this->entityManager->createQuery(
            'SELECT r FROM '.AbsenceRequest::class.' r'
            .' WHERE r.tenantId = :tenant AND r.userId = :user ORDER BY r.startDate DESC',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('user', $userId)
            ->getResult();

        return $requests;
    }

    public function findValidatedCovering(TenantId $tenant, string $userId, DateTimeImmutable $day): ?AbsenceRequest
    {
        /** @var AbsenceRequest|null $found */
        $found = $this->entityManager->createQuery(
            'SELECT r FROM '.AbsenceRequest::class.' r'
            .' WHERE r.tenantId = :tenant AND r.userId = :user AND r.status = :validated'
            .' AND r.startDate <= :day AND r.endDate >= :day',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('user', $userId)
            ->setParameter('validated', AbsenceStatus::VALIDATED->value)
            ->setParameter('day', $day, 'date_immutable')
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $found;
    }

    public function findValidatedOverlapping(TenantId $tenant, string $userId, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        /** @var list<AbsenceRequest> $requests */
        $requests = $this->entityManager->createQuery(
            'SELECT r FROM '.AbsenceRequest::class.' r'
            .' WHERE r.tenantId = :tenant AND r.userId = :user AND r.status = :validated'
            .' AND r.startDate <= :to AND r.endDate >= :from',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('user', $userId)
            ->setParameter('validated', AbsenceStatus::VALIDATED->value)
            ->setParameter('from', $from, 'date_immutable')
            ->setParameter('to', $to, 'date_immutable')
            ->getResult();

        return $requests;
    }
}
