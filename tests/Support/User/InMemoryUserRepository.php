<?php

declare(strict_types=1);

namespace App\Tests\Support\User;

use App\Domain\Tenant\TenantId;
use App\Domain\User\UserRepository;

final class InMemoryUserRepository implements UserRepository
{
    /** @var array<string, true> clés « tenant|userId » des utilisateurs connus */
    private array $known = [];

    public function register(TenantId $tenant, string $userId): void
    {
        $this->known[$this->key($tenant, $userId)] = true;
    }

    public function existsInTenant(TenantId $tenant, string $userId): bool
    {
        return isset($this->known[$this->key($tenant, $userId)]);
    }

    public function findIdsByTenant(TenantId $tenant): array
    {
        $prefix = $tenant->toString().'|';
        $ids = [];
        foreach (array_keys($this->known) as $key) {
            if (str_starts_with((string) $key, $prefix)) {
                $ids[] = substr((string) $key, strlen($prefix));
            }
        }

        return $ids;
    }

    private function key(TenantId $tenant, string $userId): string
    {
        return $tenant->toString().'|'.$userId;
    }
}
