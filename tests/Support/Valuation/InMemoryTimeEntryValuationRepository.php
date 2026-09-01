<?php

declare(strict_types=1);

namespace App\Tests\Support\Valuation;

use App\Domain\Tenant\TenantId;
use App\Domain\Valuation\TimeEntryValuation;
use App\Domain\Valuation\TimeEntryValuationRepository;
use App\Domain\Valuation\ValuationStatus;

final class InMemoryTimeEntryValuationRepository implements TimeEntryValuationRepository
{
    /** @var list<TimeEntryValuation> */
    public array $valuations = [];

    public function save(TimeEntryValuation $valuation): void
    {
        // Une seule valorisation par imputation : remplace la précédente (re-valorisation).
        $this->valuations = array_values(array_filter(
            $this->valuations,
            static fn (TimeEntryValuation $existing): bool => !$existing->tenantId()->equals($valuation->tenantId()) || $existing->timeEntryId() !== $valuation->timeEntryId(),
        ));
        $this->valuations[] = $valuation;
    }

    public function findForTimeEntry(TenantId $tenant, string $timeEntryId): ?TimeEntryValuation
    {
        foreach ($this->valuations as $valuation) {
            if ($valuation->tenantId()->equals($tenant) && $valuation->timeEntryId() === $timeEntryId) {
                return $valuation;
            }
        }

        return null;
    }

    public function findMissingRate(TenantId $tenant): array
    {
        return array_values(array_filter(
            $this->valuations,
            static fn (TimeEntryValuation $v): bool => $v->tenantId()->equals($tenant)
                && ValuationStatus::MISSING_RATE === $v->status(),
        ));
    }
}
