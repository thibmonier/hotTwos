<?php

declare(strict_types=1);

namespace App\Tests\Support\Reminder;

use App\Domain\Reminder\ReminderPreference;
use App\Domain\Reminder\ReminderPreferenceRepository;
use App\Domain\Tenant\TenantId;

final class InMemoryReminderPreferenceRepository implements ReminderPreferenceRepository
{
    /** @var list<ReminderPreference> */
    public array $preferences = [];

    public function save(ReminderPreference $preference): void
    {
        foreach ($this->preferences as $existing) {
            if ($existing === $preference) {
                return;
            }
        }
        $this->preferences[] = $preference;
    }

    public function findForUser(TenantId $tenant, string $userId): ?ReminderPreference
    {
        foreach ($this->preferences as $preference) {
            if ($preference->tenantId()->equals($tenant) && $preference->userId() === $userId) {
                return $preference;
            }
        }

        return null;
    }

    public function findOptedOutUserIds(TenantId $tenant): array
    {
        $ids = [];
        foreach ($this->preferences as $preference) {
            if ($preference->tenantId()->equals($tenant) && $preference->isOptedOut()) {
                $ids[] = $preference->userId();
            }
        }

        return $ids;
    }
}
