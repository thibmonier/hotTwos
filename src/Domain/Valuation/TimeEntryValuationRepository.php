<?php

declare(strict_types=1);

namespace App\Domain\Valuation;

use App\Domain\Tenant\TenantId;
use DateTimeImmutable;

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

    /**
     * Agrégat du tableau de bord financier (US-060, T-060-06) : avancement, CA et coût cumulés,
     * fraîcheur — en une seule agrégation, sans charger les lignes.
     */
    public function summaryFor(TenantId $tenant): ValuationSummary;

    /**
     * Dernières valorisations abouties (`valued`), pour l'audit trail du taux appliqué.
     *
     * @return list<TimeEntryValuation>
     */
    public function findValued(TenantId $tenant, int $limit): array;

    /**
     * Ventilation par projet des valorisations abouties (US-060, T-060-04) : CA, coût et marge par
     * projet, triés du CA décroissant. Le rattachement au projet passe par le join
     * `time_entry_valuation ↔ time_entry` (le snapshot ne porte pas `project_id`).
     *
     * @return list<ProjectValuationLine>
     */
    public function projectBreakdownFor(TenantId $tenant): array;

    /**
     * Ventilation par projet des valorisations abouties **sur une période** `[from, to)` (US-071,
     * T-071-04) : CA reconnu, coût et marge par projet pour figer la marge à la clôture. Même
     * rattachement projet que {@see projectBreakdownFor()} (join `time_entry_valuation ↔ time_entry`),
     * borné à la date de prestation du mois clôturé.
     *
     * @return list<ProjectValuationLine>
     */
    public function projectBreakdownForPeriod(TenantId $tenant, DateTimeImmutable $from, DateTimeImmutable $to): array;

    /**
     * Nombre d'imputations non valorisées (`MISSING_RATE`, CA-4) par projet **sur une période**
     * `[from, to)` (US-071, T-071-04) — sert à marquer une marge « partielle » et à en indiquer le
     * volume. Rattachement projet via le join `time_entry_valuation ↔ time_entry`.
     *
     * @return array<string, int> projectId => imputations non valorisées
     */
    public function missingRateCountByProjectForPeriod(TenantId $tenant, DateTimeImmutable $from, DateTimeImmutable $to): array;

    /**
     * Date de prestation la plus récente parmi les valorisations abouties (US-060, T-060-03) —
     * sert à cadrer le mois de référence de l'occupation. `null` si aucune valorisation.
     */
    public function latestValuedWorkDate(TenantId $tenant): ?DateTimeImmutable;

    /**
     * Nombre de jours (dates de prestation distinctes) valorisés par collaborateur sur `[from, to)`
     * (US-060, T-060-03). Rattachement via le join `time_entry_valuation ↔ time_entry`.
     *
     * @return array<string, int> userId => jours valorisés distincts
     */
    public function valuedDayCountByUser(TenantId $tenant, DateTimeImmutable $from, DateTimeImmutable $to): array;
}
