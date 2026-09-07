<?php

declare(strict_types=1);

namespace App\Tests\Support\Calendar;

use App\Domain\Calendar\WorkSchedule;
use App\Domain\Calendar\WorkScheduleRepository;
use App\Domain\Tenant\TenantId;

final class InMemoryWorkScheduleRepository implements WorkScheduleRepository
{
    /** @var list<WorkSchedule> */
    public array $schedules = [];

    public function save(WorkSchedule $schedule): void
    {
        foreach ($this->schedules as $i => $existing) {
            if ($existing->tenantId()->equals($schedule->tenantId()) && $existing->userId() === $schedule->userId()) {
                $this->schedules[$i] = $schedule;

                return;
            }
        }
        $this->schedules[] = $schedule;
    }

    public function delete(WorkSchedule $schedule): void
    {
        $this->schedules = array_values(array_filter(
            $this->schedules,
            static fn (WorkSchedule $s): bool => !$s->tenantId()->equals($schedule->tenantId()) || $s->userId() !== $schedule->userId(),
        ));
    }

    public function find(TenantId $tenant, string $userId): ?WorkSchedule
    {
        foreach ($this->schedules as $schedule) {
            if ($schedule->tenantId()->equals($tenant) && $schedule->userId() === $userId) {
                return $schedule;
            }
        }

        return null;
    }

    public function findForTenant(TenantId $tenant): array
    {
        return array_values(array_filter(
            $this->schedules,
            static fn (WorkSchedule $s): bool => $s->tenantId()->equals($tenant),
        ));
    }
}
