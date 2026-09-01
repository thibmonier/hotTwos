<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Period\AccountingPeriod;
use App\Domain\Period\AccountingPeriodRepository;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Implémentation Doctrine du {@see AccountingPeriodRepository} (US-057, DIP). Tenant explicite.
 */
final readonly class DoctrineAccountingPeriodRepository implements AccountingPeriodRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(AccountingPeriod $period): void
    {
        $this->entityManager->persist($period);
        $this->entityManager->flush();
    }

    public function findByPeriod(TenantId $tenant, string $period): ?AccountingPeriod
    {
        /** @var AccountingPeriod|null $found */
        $found = $this->entityManager->createQuery(
            'SELECT p FROM '.AccountingPeriod::class.' p WHERE p.tenantId = :tenant AND p.period = :period',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('period', $period)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $found;
    }

    public function findAllByTenant(TenantId $tenant): array
    {
        /** @var list<AccountingPeriod> $periods */
        $periods = $this->entityManager->createQuery(
            'SELECT p FROM '.AccountingPeriod::class.' p WHERE p.tenantId = :tenant ORDER BY p.period DESC',
        )->setParameter('tenant', $tenant->toString())->getResult();

        return $periods;
    }
}
