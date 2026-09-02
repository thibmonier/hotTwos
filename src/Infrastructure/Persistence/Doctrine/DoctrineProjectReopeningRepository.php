<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Project\ProjectReopening;
use App\Domain\Project\ProjectReopeningRepository;
use App\Domain\Tenant\TenantId;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Implémentation Doctrine du {@see ProjectReopeningRepository} (US-038, DIP). Tenant explicite.
 */
final readonly class DoctrineProjectReopeningRepository implements ProjectReopeningRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(ProjectReopening $reopening): void
    {
        $this->entityManager->persist($reopening);
        $this->entityManager->flush();
    }

    public function find(TenantId $tenant, string $reopeningId): ?ProjectReopening
    {
        /** @var ProjectReopening|null $reopening */
        $reopening = $this->entityManager->createQuery(
            'SELECT r FROM '.ProjectReopening::class.' r WHERE r.tenantId = :tenant AND r.id = :id',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('id', $reopeningId)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $reopening;
    }

    public function findForProject(TenantId $tenant, string $projectId): array
    {
        /** @var list<ProjectReopening> $reopenings */
        $reopenings = $this->entityManager->createQuery(
            'SELECT r FROM '.ProjectReopening::class.' r WHERE r.tenantId = :tenant AND r.projectId = :project ORDER BY r.requestedAt DESC',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('project', $projectId)
            ->getResult();

        return $reopenings;
    }

    public function hasActiveOn(TenantId $tenant, string $projectId, DateTimeImmutable $day): bool
    {
        $count = $this->entityManager->createQuery(
            'SELECT COUNT(r.id) FROM '.ProjectReopening::class.' r'
            .' WHERE r.tenantId = :tenant AND r.projectId = :project AND r.approvedAt IS NOT NULL AND r.openUntil >= :day',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('project', $projectId)
            ->setParameter('day', $day, 'date_immutable')
            ->getSingleScalarResult();

        return is_numeric($count) && (int) $count > 0;
    }
}
