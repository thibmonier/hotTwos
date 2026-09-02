<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Project\ProjectMilestone;
use App\Domain\Project\ProjectMilestoneRepository;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Implémentation Doctrine du {@see ProjectMilestoneRepository} (US-031, DIP). Tenant explicite.
 */
final readonly class DoctrineProjectMilestoneRepository implements ProjectMilestoneRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(ProjectMilestone $milestone): void
    {
        $this->entityManager->persist($milestone);
        $this->entityManager->flush();
    }

    public function find(TenantId $tenant, string $milestoneId): ?ProjectMilestone
    {
        /** @var ProjectMilestone|null $milestone */
        $milestone = $this->entityManager->createQuery(
            'SELECT m FROM '.ProjectMilestone::class.' m WHERE m.tenantId = :tenant AND m.id = :id',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('id', $milestoneId)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $milestone;
    }

    public function findForProject(TenantId $tenant, string $projectId): array
    {
        /** @var list<ProjectMilestone> $milestones */
        $milestones = $this->entityManager->createQuery(
            'SELECT m FROM '.ProjectMilestone::class.' m WHERE m.tenantId = :tenant AND m.projectId = :project ORDER BY m.dueDate ASC',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('project', $projectId)
            ->getResult();

        return $milestones;
    }
}
