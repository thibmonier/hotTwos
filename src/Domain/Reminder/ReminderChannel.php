<?php

declare(strict_types=1);

namespace App\Domain\Reminder;

/**
 * Canal d'acheminement d'une relance de retard de saisie (US-056, EF-TMP-21).
 *
 * `in_app` : notification dans l'application. `email` : courriel. `both` : les deux. Le canal
 * effectivement émis reste soumis à l'opt-out individuel du collaborateur ({@see ReminderPreference}).
 */
enum ReminderChannel: string
{
    case IN_APP = 'in_app';
    case EMAIL = 'email';
    case BOTH = 'both';

    public function includesInApp(): bool
    {
        return self::IN_APP === $this || self::BOTH === $this;
    }

    public function includesEmail(): bool
    {
        return self::EMAIL === $this || self::BOTH === $this;
    }
}
