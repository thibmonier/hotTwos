<?php

declare(strict_types=1);

namespace App\Tests\Support\Reminder;

use App\Domain\Reminder\ReminderLog;
use App\Domain\Reminder\ReminderLogRepository;
use App\Domain\Tenant\TenantId;
use DateTimeImmutable;

final class InMemoryReminderLogRepository implements ReminderLogRepository
{
    /** @var list<ReminderLog> */
    public array $logs = [];

    public function save(ReminderLog $log): void
    {
        foreach ($this->logs as $existing) {
            if ($existing === $log) {
                return;
            }
        }
        $this->logs[] = $log;
    }

    public function latestFor(TenantId $tenant, string $userId, DateTimeImmutable $weekStart): ?ReminderLog
    {
        $latest = null;
        foreach ($this->logs as $log) {
            if ($log->tenantId()->equals($tenant)
                && $log->userId() === $userId
                && $log->weekStart()->format('Y-m-d') === $weekStart->format('Y-m-d')
                && (!$latest instanceof ReminderLog || $log->sequence() > $latest->sequence())) {
                $latest = $log;
            }
        }

        return $latest;
    }

    public function findRecent(TenantId $tenant, ?string $userId, int $limit): array
    {
        $matching = array_values(array_filter(
            $this->logs,
            static fn (ReminderLog $log): bool => $log->tenantId()->equals($tenant)
                && (null === $userId || $log->userId() === $userId),
        ));

        usort($matching, static fn (ReminderLog $a, ReminderLog $b): int => $b->sentAt() <=> $a->sentAt());

        return array_slice($matching, 0, max(1, $limit));
    }
}
