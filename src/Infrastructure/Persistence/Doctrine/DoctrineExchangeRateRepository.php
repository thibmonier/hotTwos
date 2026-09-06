<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Currency\ExchangeRate;
use App\Domain\Currency\ExchangeRateRepository;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineExchangeRateRepository implements ExchangeRateRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(ExchangeRate $rate): void
    {
        $this->entityManager->persist($rate);
        $this->entityManager->flush();
    }

    public function findForCurrency(TenantId $tenant, string $code): array
    {
        /** @var list<ExchangeRate> $rows */
        $rows = $this->entityManager->createQuery(
            'SELECT r FROM '.ExchangeRate::class.' r WHERE r.tenantId = :tenant AND r.code = :code ORDER BY r.effectiveFrom ASC',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('code', strtoupper($code))
            ->getResult();

        return $rows;
    }
}
