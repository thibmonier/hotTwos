<?php

declare(strict_types=1);

namespace App\Tests\Support\User;

use App\Domain\Tenant\TenantId;
use App\Domain\User\UserRepository;

final class InMemoryUserRepository implements UserRepository
{
    /** @var array<string, true> clés « tenant|userId » des utilisateurs connus */
    private array $known = [];

    /** @var array<string, string> */
    private array $emails = [];

    public function register(TenantId $tenant, string $userId, ?string $email = null): void
    {
        $this->known[$this->key($tenant, $userId)] = true;

        if (null !== $email) {
            $this->emails[$this->key($tenant, $userId)] = $email;
        }
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

    public function findEmailsByIds(TenantId $tenant, array $userIds): array
    {
        $emails = [];
        foreach ($userIds as $userId) {
            $key = $this->key($tenant, $userId);
            if (isset($this->emails[$key])) {
                $emails[$userId] = $this->emails[$key];
            }
        }

        return $emails;
    }

    private function key(TenantId $tenant, string $userId): string
    {
        return $tenant->toString().'|'.$userId;
    }
}
