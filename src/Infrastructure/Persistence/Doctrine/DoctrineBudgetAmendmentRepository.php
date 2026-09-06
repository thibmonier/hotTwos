<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Project\BudgetAmendment;
use App\Domain\Project\BudgetAmendmentRepository;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineBudgetAmendmentRepository implements BudgetAmendmentRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(BudgetAmendment $amendment): void
    {
        $this->entityManager->persist($amendment);
        $this->entityManager->flush();
    }

    public function findForProject(TenantId $tenant, string $projectId): array
    {
        /** @var list<BudgetAmendment> $rows */
        $rows = $this->entityManager->createQuery(
            'SELECT a FROM '.BudgetAmendment::class.' a WHERE a.tenantId = :tenant AND a.projectId = :project ORDER BY a.recordedAt ASC',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('project', $projectId)
            ->getResult();

        return $rows;
    }
}
