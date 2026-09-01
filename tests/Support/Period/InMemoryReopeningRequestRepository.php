<?php

declare(strict_types=1);

namespace App\Tests\Support\Period;

use App\Domain\Period\ReopeningRequest;
use App\Domain\Period\ReopeningRequestRepository;
use App\Domain\Tenant\TenantId;
use DateTimeImmutable;

final class InMemoryReopeningRequestRepository implements ReopeningRequestRepository
{
    /** @var list<ReopeningRequest> */
    public array $requests = [];

    public function save(ReopeningRequest $request): void
    {
        foreach ($this->requests as $existing) {
            if ($existing === $request) {
                return;
            }
        }
        $this->requests[] = $request;
    }

    public function findById(TenantId $tenant, string $id): ?ReopeningRequest
    {
        foreach ($this->requests as $request) {
            if ($request->tenantId()->equals($tenant) && $request->id() === $id) {
                return $request;
            }
        }

        return null;
    }

    public function findActiveForPeriod(TenantId $tenant, string $period, DateTimeImmutable $now): ?ReopeningRequest
    {
        foreach ($this->requests as $request) {
            if ($request->tenantId()->equals($tenant) && $request->period() === $period && $request->isActiveAt($now)) {
                return $request;
            }
        }

        return null;
    }
}
