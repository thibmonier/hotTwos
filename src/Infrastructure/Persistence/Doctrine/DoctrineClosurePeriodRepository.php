<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Calendar\ClosurePeriod;
use App\Domain\Calendar\ClosurePeriodRepository;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineClosurePeriodRepository implements ClosurePeriodRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(ClosurePeriod $closure): void
    {
        $this->entityManager->persist($closure);
        $this->entityManager->flush();
    }

    public function delete(ClosurePeriod $closure): void
    {
        $this->entityManager->remove($closure);
        $this->entityManager->flush();
    }

    public function find(TenantId $tenant, string $id): ?ClosurePeriod
    {
        $closure = $this->entityManager->find(ClosurePeriod::class, $id);

        return $closure instanceof ClosurePeriod && $closure->tenantId()->equals($tenant) ? $closure : null;
    }

    public function findForTenant(TenantId $tenant): array
    {
        /** @var list<ClosurePeriod> $closures */
        $closures = $this->entityManager->createQuery(
            'SELECT c FROM '.ClosurePeriod::class.' c WHERE c.tenantId = :tenant ORDER BY c.startDate ASC',
        )
            ->setParameter('tenant', $tenant->toString())
            ->getResult();

        return $closures;
    }
}
