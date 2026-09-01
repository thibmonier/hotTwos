<?php

declare(strict_types=1);

namespace App\Domain\Reminder;

use App\Domain\Tenant\TenantId;

/**
 * Port de persistance de la règle de relance (US-056, DIP). Tenant explicite. Une règle par tenant.
 */
interface ReminderRuleRepository
{
    public function save(ReminderRule $rule): void;

    public function findForTenant(TenantId $tenant): ?ReminderRule;
}
