<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Tenant\TenantId;
use App\Domain\Timesheet\TimeEntry;
use App\Domain\Timesheet\TimeEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use DateTimeImmutable;

/**
 * Implémentation Doctrine du {@see TimeEntryRepository} (US-050, DIP). Cloisonnement par
 * tenant explicite.
 */
final readonly class DoctrineTimeEntryRepository implements TimeEntryRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findForDay(TenantId $tenant, string $userId, string $projectId, DateTimeImmutable $workDate): ?TimeEntry
    {
        /** @var TimeEntry|null $entry */
        $entry = $this->entityManager->createQuery(
            'SELECT e FROM '.TimeEntry::class.' e'
            .' WHERE e.tenantId = :tenant AND e.userId = :user AND e.projectId = :project AND e.workDate = :date',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('user', $userId)
            ->setParameter('project', $projectId)
            ->setParameter('date', $workDate, 'date_immutable')
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $entry;
    }

    public function minutesLoggedForDay(TenantId $tenant, string $userId, DateTimeImmutable $workDate, ?string $exceptProjectId = null): int
    {
        $dql = 'SELECT COALESCE(SUM(e.minutes), 0) FROM '.TimeEntry::class.' e'
            .' WHERE e.tenantId = :tenant AND e.userId = :user AND e.workDate = :date';
        if (null !== $exceptProjectId) {
            $dql .= ' AND e.projectId <> :except';
        }

        $query = $this->entityManager->createQuery($dql)
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('user', $userId)
            ->setParameter('date', $workDate, 'date_immutable');
        if (null !== $exceptProjectId) {
            $query->setParameter('except', $exceptProjectId);
        }

        return (int) $query->getSingleScalarResult();
    }

    public function save(TimeEntry $entry): void
    {
        $this->entityManager->persist($entry);
        $this->entityManager->flush();
    }
}
