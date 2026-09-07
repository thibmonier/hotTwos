<?php

declare(strict_types=1);

namespace App\Tests\Support\Validation;

use App\Domain\Tenant\TenantId;
use App\Domain\Validation\AbsenceValidationCircuit;
use App\Domain\Validation\AbsenceValidationCircuitRepository;

final class InMemoryAbsenceValidationCircuitRepository implements AbsenceValidationCircuitRepository
{
    /** @var list<AbsenceValidationCircuit> */
    public array $circuits = [];

    public function findForTenant(TenantId $tenant): ?AbsenceValidationCircuit
    {
        foreach ($this->circuits as $circuit) {
            if ($circuit->tenantId()->equals($tenant)) {
                return $circuit;
            }
        }

        return null;
    }

    public function save(AbsenceValidationCircuit $circuit): void
    {
        foreach ($this->circuits as $i => $existing) {
            if ($existing->tenantId()->equals($circuit->tenantId())) {
                $this->circuits[$i] = $circuit;

                return;
            }
        }
        $this->circuits[] = $circuit;
    }
}
