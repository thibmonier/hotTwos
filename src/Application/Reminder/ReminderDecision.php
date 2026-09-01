<?php

declare(strict_types=1);

namespace App\Application\Reminder;

use App\Domain\Reminder\ReminderChannel;
use DateTimeImmutable;

/**
 * Relance à émettre pour un collaborateur sur une semaine en retard (US-056). Résultat pur et
 * déterministe du moteur {@see ScheduleReminders} : ne porte aucun effet de bord. Le rang
 * ({@see sequence}) et l'escalade sont figés au moment de la décision.
 */
final readonly class ReminderDecision
{
    public function __construct(
        public string $userId,
        public DateTimeImmutable $weekStart,
        public ReminderChannel $channel,
        public int $sequence,
        public bool $escalated,
    ) {
    }
}
