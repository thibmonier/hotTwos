<?php

declare(strict_types=1);

namespace App\Domain\Calendar;

use App\Domain\Tenant\TenantId;

interface WorkScheduleRepository
{
    public function save(WorkSchedule $schedule): void;

    public function delete(WorkSchedule $schedule): void;

    public function find(TenantId $tenant, string $userId): ?WorkSchedule;

    /**
     * @return list<WorkSchedule>
     */
    public function findForTenant(TenantId $tenant): array;
}
