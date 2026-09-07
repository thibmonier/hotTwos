<?php

declare(strict_types=1);

namespace App\Domain\Validation;

use App\Domain\Tenant\TenantId;

interface AbsenceValidationCircuitRepository
{
    public function findForTenant(TenantId $tenant): ?AbsenceValidationCircuit;

    public function save(AbsenceValidationCircuit $circuit): void;
}
