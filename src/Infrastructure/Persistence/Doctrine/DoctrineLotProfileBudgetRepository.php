<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Project\LotProfileBudget;
use App\Domain\Project\LotProfileBudgetRepository;
use App\Domain\Project\ProjectLot;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineLotProfileBudgetRepository implements LotProfileBudgetRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(LotProfileBudget $line): void
    {
        $this->entityManager->persist($line);
        $this->entityManager->flush();
    }

    public function findForLot(TenantId $tenant, string $lotId): array
    {
        /** @var list<LotProfileBudget> $rows */
        $rows = $this->entityManager->createQuery(
            'SELECT b FROM '.LotProfileBudget::class.' b WHERE b.tenantId = :tenant AND b.lotId = :lot',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('lot', $lotId)
            ->getResult();

        return $rows;
    }

    public function findForProject(TenantId $tenant, string $projectId): array
    {
        /** @var list<LotProfileBudget> $rows */
        $rows = $this->entityManager->createQuery(
            'SELECT b FROM '.LotProfileBudget::class.' b, '.ProjectLot::class.' l '
            .'WHERE b.lotId = l.id AND b.tenantId = :tenant AND l.projectId = :project',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('project', $projectId)
            ->getResult();

        return $rows;
    }
}
