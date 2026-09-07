<?php

declare(strict_types=1);

namespace App\Domain\Calendar;

use App\Domain\Tenant\TenantId;
use DateTimeImmutable;

interface HolidayRepository
{
    public function save(Holiday $holiday): void;

    public function delete(Holiday $holiday): void;

    public function find(TenantId $tenant, string $id): ?Holiday;

    /**
     * Tous les jours fériés du tenant, ordonnés par date croissante.
     *
     * @return list<Holiday>
     */
    public function findForTenant(TenantId $tenant): array;

    /**
     * Dates fériées du tenant (pour le calcul des jours ouvrés).
     *
     * @return list<DateTimeImmutable>
     */
    public function allDatesForTenant(TenantId $tenant): array;

    public function existsForDate(TenantId $tenant, DateTimeImmutable $date): bool;
}
