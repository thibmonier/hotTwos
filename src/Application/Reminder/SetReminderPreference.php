<?php

declare(strict_types=1);

namespace App\Application\Reminder;

use App\Domain\Authorization\SecurityAuditLogger;
use App\Domain\Reminder\ReminderPreference;
use App\Domain\Reminder\ReminderPreferenceRepository;
use App\Domain\User\User;
use Psr\Clock\ClockInterface;

/**
 * Préférence individuelle de relance (US-056, CA-2, RGPD). L'opt-out est un **droit du collaborateur**
 * exercé sur sa **propre** préférence : le cas d'usage n'agit que sur l'utilisateur courant, jamais
 * sur un tiers — un administrateur ne peut donc pas forcer la réactivation d'un opt-out (non forçable).
 */
final readonly class SetReminderPreference
{
    public function __construct(
        private ReminderPreferenceRepository $preferences,
        private SecurityAuditLogger $audit,
        private ClockInterface $clock,
    ) {
    }

    public function current(User $user): ReminderPreference
    {
        return $this->preferences->findForUser($user->tenantId(), $user->id())
            ?? new ReminderPreference($user->tenantId(), $user->id(), false, $this->clock->now());
    }

    public function optOut(User $user): ReminderPreference
    {
        return $this->apply($user, true);
    }

    public function optIn(User $user): ReminderPreference
    {
        return $this->apply($user, false);
    }

    private function apply(User $user, bool $optedOut): ReminderPreference
    {
        $tenant = $user->tenantId();
        $now = $this->clock->now();

        $preference = $this->preferences->findForUser($tenant, $user->id());
        if (!$preference instanceof ReminderPreference) {
            $preference = new ReminderPreference($tenant, $user->id(), $optedOut, $now);
        } elseif ($optedOut) {
            $preference->optOut($now);
        } else {
            $preference->optIn($now);
        }

        $this->preferences->save($preference);
        $this->audit->record($optedOut ? 'reminder_opt_out' : 'reminder_opt_in', $tenant->toString(), $user->getUserIdentifier());

        return $preference;
    }
}
