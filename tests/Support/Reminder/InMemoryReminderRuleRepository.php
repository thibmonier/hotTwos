<?php

declare(strict_types=1);

namespace App\Tests\Support\Reminder;

use App\Domain\Reminder\ReminderRule;
use App\Domain\Reminder\ReminderRuleRepository;
use App\Domain\Tenant\TenantId;

final class InMemoryReminderRuleRepository implements ReminderRuleRepository
{
    /** @var list<ReminderRule> */
    public array $rules = [];

    public function save(ReminderRule $rule): void
    {
        foreach ($this->rules as $existing) {
            if ($existing === $rule) {
                return;
            }
        }
        $this->rules[] = $rule;
    }

    public function findForTenant(TenantId $tenant): ?ReminderRule
    {
        foreach ($this->rules as $rule) {
            if ($rule->tenantId()->equals($tenant)) {
                return $rule;
            }
        }

        return null;
    }
}
