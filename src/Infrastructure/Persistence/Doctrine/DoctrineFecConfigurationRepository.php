<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Fec\FecConfiguration;
use App\Domain\Fec\FecConfigurationRepository;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Implémentation Doctrine du {@see FecConfigurationRepository} (US-074, DIP). Tenant explicite.
 */
final readonly class DoctrineFecConfigurationRepository implements FecConfigurationRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findForTenant(TenantId $tenant): ?FecConfiguration
    {
        /** @var FecConfiguration|null $config */
        $config = $this->entityManager->createQuery(
            'SELECT c FROM '.FecConfiguration::class.' c WHERE c.tenantId = :tenant',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $config;
    }

    public function save(FecConfiguration $configuration): void
    {
        $this->entityManager->persist($configuration);
        $this->entityManager->flush();
    }
}
