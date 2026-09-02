<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Project\ExternalCommitment;
use App\Domain\Project\ExternalCommitmentRepository;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Implémentation Doctrine du {@see ExternalCommitmentRepository} (US-034, DIP). Tenant explicite.
 */
final readonly class DoctrineExternalCommitmentRepository implements ExternalCommitmentRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(ExternalCommitment $commitment): void
    {
        $this->entityManager->persist($commitment);
        $this->entityManager->flush();
    }

    public function findForProject(TenantId $tenant, string $projectId): array
    {
        /** @var list<ExternalCommitment> $commitments */
        $commitments = $this->entityManager->createQuery(
            'SELECT c FROM '.ExternalCommitment::class.' c WHERE c.tenantId = :tenant AND c.projectId = :project ORDER BY c.type ASC',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('project', $projectId)
            ->getResult();

        return $commitments;
    }
}
