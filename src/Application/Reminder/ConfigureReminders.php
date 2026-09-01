<?php

declare(strict_types=1);

namespace App\Application\Reminder;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\SecurityAuditLogger;
use App\Domain\Reminder\ReminderChannel;
use App\Domain\Reminder\ReminderException;
use App\Domain\Reminder\ReminderRule;
use App\Domain\Reminder\ReminderRuleRepository;
use App\Domain\User\User;
use InvalidArgumentException;

/**
 * Paramétrage des relances de retard de saisie d'un tenant (US-056, T-056-04). Habilitation
 * `MANAGE_REMINDERS` (403 sinon). Une règle par tenant : la lecture renvoie la règle en vigueur ou
 * une configuration de référence transitoire tant qu'aucune n'a été définie.
 */
final readonly class ConfigureReminders
{
    public function __construct(
        private Authorizer $authorizer,
        private ReminderRuleRepository $rules,
        private SecurityAuditLogger $audit,
    ) {
    }

    public function current(User $user): ReminderRule
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_REMINDERS);

        return $this->rules->findForTenant($user->tenantId()) ?? ReminderRule::default($user->tenantId());
    }

    public function update(
        User $user,
        int $initialDelayDays,
        int $frequencyDays,
        ReminderChannel $channel,
        bool $escalationEnabled,
        bool $active,
    ): ReminderRule {
        $this->authorizer->ensureCan($user, Permission::MANAGE_REMINDERS);
        $tenant = $user->tenantId();

        try {
            $rule = $this->rules->findForTenant($tenant);
            if (!$rule instanceof ReminderRule) {
                $rule = new ReminderRule($tenant, $initialDelayDays, $frequencyDays, $channel, $escalationEnabled, $active);
            } else {
                $rule->reconfigure($initialDelayDays, $frequencyDays, $channel, $escalationEnabled);
                $active ? $rule->activate() : $rule->deactivate();
            }
        } catch (InvalidArgumentException $exception) {
            throw new ReminderException($exception->getMessage(), $exception->getCode(), $exception);
        }

        $this->rules->save($rule);
        $this->audit->record('reminder_rule_updated', $tenant->toString(), $user->getUserIdentifier(), [
            'active' => $active ? '1' : '0',
        ]);

        return $rule;
    }
}
