<?php

declare(strict_types=1);

namespace App\Tests\Support\Valuation;

use App\Domain\Tenant\TenantId;
use App\Domain\Valuation\ProjectValuationLine;
use App\Domain\Valuation\TimeEntryValuation;
use App\Domain\Valuation\TimeEntryValuationRepository;
use App\Domain\Valuation\ValuationStatus;
use App\Domain\Valuation\ValuationSummary;
use DateTimeImmutable;

final class InMemoryTimeEntryValuationRepository implements TimeEntryValuationRepository
{
    /** @var list<TimeEntryValuation> */
    public array $valuations = [];

    /**
     * Ventilation par projet — le rattachement projet vit dans `time_entry` (hors de ce fake) :
     * les tests la fixent explicitement plutôt que de la recalculer.
     *
     * @var list<ProjectValuationLine>
     */
    public array $projectBreakdown = [];

    /** Occupation (T-060-03) : le rattachement date/collaborateur vit dans `time_entry` (hors fake). */
    public ?DateTimeImmutable $latestValuedWorkDate = null;

    /** @var array<string, int> userId => jours valorisés distincts, fixés par les tests. */
    public array $valuedDayCountByUser = [];

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

    public function summaryFor(TenantId $tenant): ValuationSummary
    {
        $own = array_filter($this->valuations, static fn (TimeEntryValuation $v): bool => $v->tenantId()->equals($tenant));

        $valued = 0;
        $missing = 0;
        $revenue = 0;
        $cost = 0;
        $latest = null;
        foreach ($own as $v) {
            $revenue += $v->revenueCents();
            $cost += $v->costCents();
            if (ValuationStatus::VALUED === $v->status()) {
                ++$valued;
            } elseif (ValuationStatus::MISSING_RATE === $v->status()) {
                ++$missing;
            }
            if (null === $latest || $v->valuedAt() > $latest) {
                $latest = $v->valuedAt();
            }
        }

        return new ValuationSummary(count($own), $valued, $missing, $revenue, $cost, $latest);
    }

    public function findValued(TenantId $tenant, int $limit): array
    {
        $valued = array_values(array_filter(
            $this->valuations,
            static fn (TimeEntryValuation $v): bool => $v->tenantId()->equals($tenant)
                && ValuationStatus::VALUED === $v->status(),
        ));
        usort($valued, static fn (TimeEntryValuation $a, TimeEntryValuation $b): int => $b->valuedAt() <=> $a->valuedAt());

        return array_slice($valued, 0, $limit);
    }

    public function projectBreakdownFor(TenantId $tenant): array
    {
        return $this->projectBreakdown;
    }

    public function latestValuedWorkDate(TenantId $tenant): ?DateTimeImmutable
    {
        return $this->latestValuedWorkDate;
    }

    public function valuedDayCountByUser(TenantId $tenant, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        return $this->valuedDayCountByUser;
    }
}
