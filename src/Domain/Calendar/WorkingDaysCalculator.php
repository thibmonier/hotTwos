<?php

declare(strict_types=1);

namespace App\Domain\Calendar;

use App\Domain\Tenant\TenantId;
use DateTimeImmutable;

/**
 * US-012 (EF-REF-6) — calcul **unifié** des jours ouvrés : exclut les week-ends **et** les jours fériés
 * du tenant. Remplace les implémentations inline dupliquées (occupation, complétude, activité, relances).
 *
 * Les fériés du tenant sont chargés une seule fois puis mémorisés (cache par tenant sur la durée de vie
 * du service) pour éviter une requête par jour dans les boucles.
 */
final class WorkingDaysCalculator
{
    private const int SATURDAY = 6;

    /** @var array<string, array<string, true>> tenantId => set de dates 'Y-m-d' fériées */
    private array $holidayCache = [];

    public function __construct(private readonly HolidayRepository $holidays)
    {
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

        return !isset($this->holidaySet($tenant)[$day->format('Y-m-d')]);
    }

    /**
     * @return array<string, true>
     */
    private function holidaySet(TenantId $tenant): array
    {
        return $this->holidayCache[$tenant->toString()] ??= $this->loadHolidaySet($tenant);
    }

    /**
     * @return array<string, true>
     */
    private function loadHolidaySet(TenantId $tenant): array
    {
        $set = [];
        foreach ($this->holidays->allDatesForTenant($tenant) as $date) {
            $set[$date->format('Y-m-d')] = true;
        }

        return $set;
    }
}
