<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Budget\ChargeLandingSnapshot;
use App\Domain\Budget\ChargeLandingSnapshotRepository;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineChargeLandingSnapshotRepository implements ChargeLandingSnapshotRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function replaceForPeriod(TenantId $tenant, string $period, array $snapshots): void
    {
        $this->entityManager->wrapInTransaction(function () use ($tenant, $period, $snapshots): void {
            $this->entityManager->createQuery(
                'DELETE FROM '.ChargeLandingSnapshot::class.' s WHERE s.tenantId = :tenant AND s.period = :period',
            )
                ->setParameter('tenant', $tenant->toString())
                ->setParameter('period', $period)
                ->execute();

            foreach ($snapshots as $snapshot) {
                $this->entityManager->persist($snapshot);
            }

            $this->entityManager->flush();
        });
    }

    public function findForProject(TenantId $tenant, string $projectId): array
    {
        /** @var list<ChargeLandingSnapshot> $snapshots */
        $snapshots = $this->entityManager->createQuery(
            'SELECT s FROM '.ChargeLandingSnapshot::class.' s'
            .' WHERE s.tenantId = :tenant AND s.projectId = :project ORDER BY s.period ASC',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('project', $projectId)
            ->getResult();

        return $snapshots;
    }
}
