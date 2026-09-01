<?php

declare(strict_types=1);

namespace App\Tests\Support\Absence;

use App\Domain\Absence\AbsenceType;
use App\Domain\Absence\AbsenceTypeRepository;
use App\Domain\Tenant\TenantId;

final class InMemoryAbsenceTypeRepository implements AbsenceTypeRepository
{
    /** @var list<AbsenceType> */
    public array $types = [];

    public function save(AbsenceType $type): void
    {
        foreach ($this->types as $existing) {
            if ($existing === $type) {
                return;
            }
        }
        $this->types[] = $type;
    }

    public function findById(TenantId $tenant, string $id): ?AbsenceType
    {
        foreach ($this->types as $type) {
            if ($type->tenantId()->equals($tenant) && $type->id() === $id) {
                return $type;
            }
        }

        return null;
    }

    public function findAllByTenant(TenantId $tenant): array
    {
        return array_values(array_filter(
            $this->types,
            static fn (AbsenceType $t): bool => $t->tenantId()->equals($tenant),
        ));
    }
}
