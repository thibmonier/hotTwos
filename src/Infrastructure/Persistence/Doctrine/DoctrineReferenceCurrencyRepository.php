<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Currency\ReferenceCurrency;
use App\Domain\Currency\ReferenceCurrencyRepository;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineReferenceCurrencyRepository implements ReferenceCurrencyRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findForTenant(TenantId $tenant): ?ReferenceCurrency
    {
        $reference = $this->entityManager->createQuery(
            'SELECT r FROM '.ReferenceCurrency::class.' r WHERE r.tenantId = :tenant',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $reference instanceof ReferenceCurrency ? $reference : null;
    }

    public function save(ReferenceCurrency $reference): void
    {
        $this->entityManager->persist($reference);
        $this->entityManager->flush();
    }
}
