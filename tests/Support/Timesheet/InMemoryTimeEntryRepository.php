<?php

declare(strict_types=1);

namespace App\Tests\Support\Timesheet;

use App\Domain\Tenant\TenantId;
use App\Domain\Timesheet\TimeEntry;
use App\Domain\Timesheet\TimeEntryRepository;
use DateTimeImmutable;

final class InMemoryTimeEntryRepository implements TimeEntryRepository
{
    /** @var list<TimeEntry> */
    public array $entries = [];

    public function findForDay(TenantId $tenant, string $userId, string $projectId, DateTimeImmutable $workDate): ?TimeEntry
    {
        foreach ($this->entries as $entry) {
            if ($this->matches($entry, $tenant, $userId, $workDate) && $entry->projectId() === $projectId) {
                return $entry;
            }
        }

        return null;
    }

    public function minutesLoggedForDay(TenantId $tenant, string $userId, DateTimeImmutable $workDate, ?string $exceptProjectId = null): int
    {
        $total = 0;
        foreach ($this->entries as $entry) {
            if ($this->matches($entry, $tenant, $userId, $workDate) && $entry->projectId() !== $exceptProjectId) {
                $total += $entry->minutes();
            }
        }

        return $total;
    }

    public function findForUserInRange(TenantId $tenant, string $userId, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $fromKey = $from->format('Y-m-d');
        $toKey = $to->format('Y-m-d');

        return array_values(array_filter($this->entries, static function (TimeEntry $entry) use ($tenant, $userId, $fromKey, $toKey): bool {
            $day = $entry->workDate()->format('Y-m-d');

            return $entry->tenantId()->equals($tenant) && $entry->userId() === $userId && $day >= $fromKey && $day <= $toKey;
        }));
    }

    public function save(TimeEntry $entry): void
    {
        foreach ($this->entries as $existing) {
            if ($existing === $entry) {
                return;
            }
        }
        $this->entries[] = $entry;
    }

    private function matches(TimeEntry $entry, TenantId $tenant, string $userId, DateTimeImmutable $workDate): bool
    {
        return $entry->tenantId()->equals($tenant)
            && $entry->userId() === $userId
            && $entry->workDate()->format('Y-m-d') === $workDate->format('Y-m-d');
    }
}
