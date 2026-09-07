<?php

declare(strict_types=1);

namespace App\Tests\Support\Calendar;

use App\Domain\Calendar\ClosurePeriod;
use App\Domain\Calendar\ClosurePeriodRepository;
use App\Domain\Tenant\TenantId;

final class InMemoryClosurePeriodRepository implements ClosurePeriodRepository
{
    /** @var list<ClosurePeriod> */
    public array $closures = [];

    public function save(ClosurePeriod $closure): void
    {
        $this->closures[] = $closure;
    }

    public function delete(ClosurePeriod $closure): void
    {
        $this->closures = array_values(array_filter(
            $this->closures,
            static fn (ClosurePeriod $c): bool => $c->id() !== $closure->id(),
        ));
    }

    public function find(TenantId $tenant, string $id): ?ClosurePeriod
    {
        foreach ($this->closures as $closure) {
            if ($closure->tenantId()->equals($tenant) && $closure->id() === $id) {
                return $closure;
            }
        }

        return null;
    }

    public function findForTenant(TenantId $tenant): array
    {
        $found = array_values(array_filter(
            $this->closures,
            static fn (ClosurePeriod $c): bool => $c->tenantId()->equals($tenant),
        ));
        usort($found, static fn (ClosurePeriod $a, ClosurePeriod $b): int => $a->startDate() <=> $b->startDate());

        return $found;
    }
}
