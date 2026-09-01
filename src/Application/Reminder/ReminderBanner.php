<?php

declare(strict_types=1);

namespace App\Application\Reminder;

use App\Application\Completeness\CompletenessGrid;
use App\Domain\Completeness\CompletenessState;
use App\Domain\Completeness\WeekCompleteness;
use App\Domain\Reminder\ReminderPreference;
use App\Domain\Reminder\ReminderPreferenceRepository;
use App\Domain\User\User;
use Psr\Clock\ClockInterface;

/**
 * Rappel discret de retard dans l'écran de saisie (US-056, T-056-05). N'est affiché **que** pour un
 * collaborateur ayant fait opt-out (CA-2) : il ne reçoit alors aucune relance poussée, mais l'écran
 * de saisie lui rappelle factuellement son retard. Renvoie le nombre de semaines en retard, ou 0
 * (aucun bandeau) — si le collaborateur reçoit les relances, ou s'il n'a aucun retard.
 */
final readonly class ReminderBanner
{
    private const int WEEKS = 4;

    public function __construct(
        private ReminderPreferenceRepository $preferences,
        private CompletenessGrid $completeness,
        private ClockInterface $clock,
    ) {
    }

    public function lateWeeksForOptedOut(User $user): int
    {
        $preference = $this->preferences->findForUser($user->tenantId(), $user->id());
        if (!$preference instanceof ReminderPreference || !$preference->isOptedOut()) {
            return 0;
        }

        $cells = $this->completeness->build($user->tenantId(), [$user->id()], $this->clock->now(), self::WEEKS);

        return count(array_filter($cells, static fn (WeekCompleteness $week): bool => CompletenessState::PARTIAL === $week->state
            || CompletenessState::EMPTY_LATE === $week->state));
    }
}
