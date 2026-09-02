<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Project\ProjectLot;
use App\Domain\Project\ProjectLotRepository;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Implémentation Doctrine du {@see ProjectLotRepository} (US-031, DIP). Tenant explicite.
 */
final readonly class DoctrineProjectLotRepository implements ProjectLotRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(ProjectLot $lot): void
    {
        $this->entityManager->persist($lot);
        $this->entityManager->flush();
    }

    public function find(TenantId $tenant, string $lotId): ?ProjectLot
    {
        /** @var ProjectLot|null $lot */
        $lot = $this->entityManager->createQuery(
            'SELECT l FROM '.ProjectLot::class.' l WHERE l.tenantId = :tenant AND l.id = :id',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('id', $lotId)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $lot;
    }

    public function findForProject(TenantId $tenant, string $projectId): array
    {
        /** @var list<ProjectLot> $lots */
        $lots = $this->entityManager->createQuery(
            'SELECT l FROM '.ProjectLot::class.' l WHERE l.tenantId = :tenant AND l.projectId = :project ORDER BY l.name ASC',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('project', $projectId)
            ->getResult();

        return $lots;
    }
}
