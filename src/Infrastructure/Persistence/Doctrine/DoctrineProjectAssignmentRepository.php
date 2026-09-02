<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Project\ProjectAssignment;
use App\Domain\Project\ProjectAssignmentRepository;
use App\Domain\Tenant\TenantId;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Implémentation Doctrine du {@see ProjectAssignmentRepository} (US-037, DIP). Tenant explicite.
 */
final readonly class DoctrineProjectAssignmentRepository implements ProjectAssignmentRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(ProjectAssignment $assignment): void
    {
        $this->entityManager->persist($assignment);
        $this->entityManager->flush();
    }

    public function find(TenantId $tenant, string $assignmentId): ?ProjectAssignment
    {
        /** @var ProjectAssignment|null $assignment */
        $assignment = $this->entityManager->createQuery(
            'SELECT a FROM '.ProjectAssignment::class.' a WHERE a.tenantId = :tenant AND a.id = :id',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('id', $assignmentId)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $assignment;
    }

    public function remove(ProjectAssignment $assignment): void
    {
        $this->entityManager->remove($assignment);
        $this->entityManager->flush();
    }

    public function findForProject(TenantId $tenant, string $projectId): array
    {
        /** @var list<ProjectAssignment> $assignments */
        $assignments = $this->entityManager->createQuery(
            'SELECT a FROM '.ProjectAssignment::class.' a WHERE a.tenantId = :tenant AND a.projectId = :project ORDER BY a.role ASC',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('project', $projectId)
            ->getResult();

        return $assignments;
    }

    public function hasAssignments(TenantId $tenant, string $projectId): bool
    {
        $count = $this->entityManager->createQuery(
            'SELECT COUNT(a.id) FROM '.ProjectAssignment::class.' a WHERE a.tenantId = :tenant AND a.projectId = :project',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('project', $projectId)
            ->getSingleScalarResult();

        return is_numeric($count) && (int) $count > 0;
    }

    public function isAssignedOn(TenantId $tenant, string $projectId, string $userId, DateTimeImmutable $day): bool
    {
        $count = $this->entityManager->createQuery(
            'SELECT COUNT(a.id) FROM '.ProjectAssignment::class.' a'
            .' WHERE a.tenantId = :tenant AND a.projectId = :project AND a.userId = :user'
            .' AND (a.startDate IS NULL OR a.startDate <= :day) AND (a.endDate IS NULL OR a.endDate >= :day)',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('project', $projectId)
            ->setParameter('user', $userId)
            ->setParameter('day', $day, 'date_immutable')
            ->getSingleScalarResult();

        return is_numeric($count) && (int) $count > 0;
    }

    public function assignedProjectIds(TenantId $tenant, string $userId): array
    {
        /** @var list<array{projectId: string}> $rows */
        $rows = $this->entityManager->createQuery(
            'SELECT DISTINCT a.projectId AS projectId FROM '.ProjectAssignment::class.' a WHERE a.tenantId = :tenant AND a.userId = :user',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('user', $userId)
            ->getResult();

        return array_map(static fn (array $row): string => $row['projectId'], $rows);
    }
}
