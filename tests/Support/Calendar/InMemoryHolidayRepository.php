<?php

declare(strict_types=1);

namespace App\Tests\Support\Calendar;

use App\Domain\Calendar\Holiday;
use App\Domain\Calendar\HolidayRepository;
use App\Domain\Tenant\TenantId;
use DateTimeImmutable;

final class InMemoryHolidayRepository implements HolidayRepository
{
    /** @var list<Holiday> */
    public array $holidays = [];

    public function save(Holiday $holiday): void
    {
        $this->holidays[] = $holiday;
    }

    public function delete(Holiday $holiday): void
    {
        $this->holidays = array_values(array_filter(
            $this->holidays,
            static fn (Holiday $h): bool => $h->id() !== $holiday->id(),
        ));
    }

    public function find(TenantId $tenant, string $id): ?Holiday
    {
        foreach ($this->holidays as $holiday) {
            if ($holiday->tenantId()->equals($tenant) && $holiday->id() === $id) {
                return $holiday;
            }
        }

        return null;
    }

    public function findForTenant(TenantId $tenant): array
    {
        $found = array_values(array_filter(
            $this->holidays,
            static fn (Holiday $h): bool => $h->tenantId()->equals($tenant),
        ));
        usort($found, static fn (Holiday $a, Holiday $b): int => $a->date() <=> $b->date());

        return $found;
    }

    public function allDatesForTenant(TenantId $tenant): array
    {
        return array_map(
            static fn (Holiday $h): DateTimeImmutable => $h->date(),
            $this->findForTenant($tenant),
        );
    }

    public function existsForDate(TenantId $tenant, DateTimeImmutable $date): bool
    {
        $target = $date->setTime(0, 0)->format('Y-m-d');

        return array_any($this->holidays, static fn (Holiday $holiday): bool => $holiday->tenantId()->equals($tenant) && $holiday->date()->format('Y-m-d') === $target);
    }
}
