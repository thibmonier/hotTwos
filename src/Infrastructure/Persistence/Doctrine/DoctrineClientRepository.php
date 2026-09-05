<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Client\Client;
use App\Domain\Client\ClientRepository;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Implémentation Doctrine du {@see ClientRepository} (US-014, DIP). Tenant explicite.
 */
final readonly class DoctrineClientRepository implements ClientRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(Client $client): void
    {
        $this->entityManager->persist($client);
        $this->entityManager->flush();
    }

    public function find(TenantId $tenant, string $clientId): ?Client
    {
        /** @var Client|null $client */
        $client = $this->entityManager->createQuery(
            'SELECT c FROM '.Client::class.' c WHERE c.tenantId = :tenant AND c.id = :id',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('id', $clientId)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $client;
    }

    public function findAllByTenant(TenantId $tenant): array
    {
        /** @var list<Client> $clients */
        $clients = $this->entityManager->createQuery(
            'SELECT c FROM '.Client::class.' c WHERE c.tenantId = :tenant ORDER BY c.name ASC',
        )
            ->setParameter('tenant', $tenant->toString())
            ->getResult();

        return $clients;
    }
}
