<?php

declare(strict_types=1);

namespace App\Domain\Valuation;

use App\Domain\Tenant\TenantId;

/**
 * Port de persistance des valorisations figées (US-060, DIP). Tenant explicite.
 */
interface TimeEntryValuationRepository
{
    public function save(TimeEntryValuation $valuation): void;

    public function findForTimeEntry(TenantId $tenant, string $timeEntryId): ?TimeEntryValuation;

    /**
     * Valorisations en attente de tarif (CA-4), à re-déclencher.
     *
     * @return list<TimeEntryValuation>
     */
    public function findMissingRate(TenantId $tenant): array;
}
