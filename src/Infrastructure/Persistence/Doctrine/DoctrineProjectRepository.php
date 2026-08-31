<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Project\Project;
use App\Domain\Project\ProjectRepository;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Implémentation Doctrine du {@see ProjectRepository} (US-050, DIP). Cloisonnement par
 * tenant exprimé explicitement, en défense en profondeur du filtre ORM.
 */
final readonly class DoctrineProjectRepository implements ProjectRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findActive(TenantId $tenant, string $projectId): ?Project
    {
        /** @var Project|null $project */
        $project = $this->entityManager->createQuery(
            'SELECT p FROM '.Project::class.' p WHERE p.id = :id AND p.tenantId = :tenant AND p.active = true',
        )
            ->setParameter('id', $projectId)
            ->setParameter('tenant', $tenant->toString())
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $project;
    }

    public function findAllActive(TenantId $tenant): array
    {
        /** @var list<Project> $projects */
        $projects = $this->entityManager->createQuery(
            'SELECT p FROM '.Project::class.' p WHERE p.tenantId = :tenant AND p.active = true ORDER BY p.code ASC',
        )->setParameter('tenant', $tenant->toString())->getResult();

        return $projects;
    }

    public function save(Project $project): void
    {
        $this->entityManager->persist($project);
        $this->entityManager->flush();
    }
}
