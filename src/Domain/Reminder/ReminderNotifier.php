<?php

declare(strict_types=1);

namespace App\Domain\Reminder;

/**
 * Port d'acheminement d'une relance (US-056, DIP). L'implémentation choisit le média selon le canal
 * de la relance ({@see ReminderLog::channel()}). La livraison effective in-app/email est une évolution
 * (dette suivie) ; l'implémentation courante trace l'intention sans dépendance externe.
 */
interface ReminderNotifier
{
    public function send(ReminderLog $reminder): void;
}
