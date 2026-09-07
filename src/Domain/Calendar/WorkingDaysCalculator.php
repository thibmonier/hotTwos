<?php

declare(strict_types=1);

namespace App\Domain\Calendar;

use App\Domain\Tenant\TenantId;
use DateTimeImmutable;

/**
 * US-012 (EF-REF-6) / US-022 (EF-REF-9) — calcul **unifié** des jours ouvrés : exclut les week-ends,
 * les jours **fériés** et les **fermetures entreprise** du tenant. Remplace les implémentations inline
 * dupliquées (occupation, complétude, activité, relances).
 *
 * Fériés et fermetures du tenant sont chargés une seule fois puis mémorisés (cache par tenant) pour
 * éviter une requête par jour dans les boucles.
 */
final class WorkingDaysCalculator
{
    private const int SATURDAY = 6;

    /** @var array<string, array<string, true>> tenantId => set de dates 'Y-m-d' non ouvrées (fériés + fermetures) */
    private array $nonWorkingCache = [];

    /** @var array<string, ?WorkSchedule> "tenantId|userId" => régime (ou null si temps plein) */
    private array $scheduleCache = [];

    public function __construct(
        private readonly HolidayRepository $holidays,
        private readonly ClosurePeriodRepository $closures,
        private readonly WorkScheduleRepository $schedules,
    ) {
    }

    /**
     * Nombre de jours ouvrés dans l'intervalle [$from, $to[ (borne haute exclue).
     */
    public function workingDaysBetween(TenantId $tenant, DateTimeImmutable $from, DateTimeImmutable $to): int
    {
        $count = 0;
        for ($day = $from; $day < $to; $day = $day->modify('+1 day')) {
            if ($this->isWorkingDay($tenant, $day)) {
                ++$count;
            }
        }

        return $count;
    }

    public function isWorkingDay(TenantId $tenant, DateTimeImmutable $day): bool
    {
        if ((int) $day->format('N') >= self::SATURDAY) {
            return false;
        }

        return !isset($this->nonWorkingSet($tenant)[$day->format('Y-m-d')]);
    }

    /**
     * US-021 — jours ouvrés d'un **collaborateur** : jour ouvré du tenant (week-end/férié/fermeture)
     * ET jour travaillé selon son régime (temps plein Lun-Ven si aucun régime).
     */
    public function workingDaysForUser(TenantId $tenant, string $userId, DateTimeImmutable $from, DateTimeImmutable $to): int
    {
        $count = 0;
        for ($day = $from; $day < $to; $day = $day->modify('+1 day')) {
            if ($this->isWorkingDayForUser($tenant, $userId, $day)) {
                ++$count;
            }
        }

        return $count;
    }

    public function isWorkingDayForUser(TenantId $tenant, string $userId, DateTimeImmutable $day): bool
    {
        if (!$this->isWorkingDay($tenant, $day)) {
            return false;
        }

        $schedule = $this->scheduleFor($tenant, $userId);
        if (!$schedule instanceof WorkSchedule) {
            return true; // temps plein par défaut
        }

        return $schedule->worksOn((int) $day->format('N'));
    }

    private function scheduleFor(TenantId $tenant, string $userId): ?WorkSchedule
    {
        $key = $tenant->toString().'|'.$userId;
        if (!array_key_exists($key, $this->scheduleCache)) {
            $this->scheduleCache[$key] = $this->schedules->find($tenant, $userId);
        }

        return $this->scheduleCache[$key];
    }

    /**
     * @return array<string, true>
     */
    private function nonWorkingSet(TenantId $tenant): array
    {
        return $this->nonWorkingCache[$tenant->toString()] ??= $this->loadNonWorkingSet($tenant);
    }

    /**
     * @return array<string, true>
     */
    private function loadNonWorkingSet(TenantId $tenant): array
    {
        $set = [];
        foreach ($this->holidays->allDatesForTenant($tenant) as $date) {
            $set[$date->format('Y-m-d')] = true;
        }
        foreach ($this->closures->findForTenant($tenant) as $closure) {
            for ($day = $closure->startDate(); $day <= $closure->endDate(); $day = $day->modify('+1 day')) {
                $set[$day->format('Y-m-d')] = true;
            }
        }

        return $set;
    }
}
