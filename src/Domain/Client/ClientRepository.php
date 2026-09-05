<?php

declare(strict_types=1);

namespace App\Domain\Client;

use App\Domain\Tenant\TenantId;

/**
 * Port de persistance des comptes clients (US-014, DIP). Tenant explicite.
 */
interface ClientRepository
{
    public function save(Client $client): void;

    public function find(TenantId $tenant, string $clientId): ?Client;

    /**
     * @return list<Client> clients du tenant, triés par nom
     */
    public function findAllByTenant(TenantId $tenant): array;
}
