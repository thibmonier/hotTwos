<?php

declare(strict_types=1);

namespace App\Application\Reminder;

use App\Application\Reminder\Message\SendDueReminders;
use App\Domain\Reminder\ReminderLog;
use App\Domain\Reminder\ReminderLogRepository;
use App\Domain\Reminder\ReminderNotifier;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Émet les relances dues d'un tenant (US-056, T-056-03). À la consommation de {@see SendDueReminders}
 * — contexte de tenant posé par le middleware, RLS armée — délègue le calcul au moteur déterministe
 * {@see ScheduleReminders}, puis pour chaque relance décidée journalise ({@see ReminderLog}, mémoire
 * du plancher et de l'escalade) et notifie le collaborateur. Handler tenant-aware écrivant en base :
 * couvert par un test d'intrusion RLS via consume (action rétro S4).
 */
#[AsMessageHandler]
final readonly class SendDueRemindersHandler
{
    public function __construct(
        private ScheduleReminders $engine,
        private ReminderLogRepository $logs,
        private ReminderNotifier $notifier,
    ) {
    }

    public function __invoke(SendDueReminders $message): void
    {
        $tenant = $message->tenantId();
        $now = $message->now();

        foreach ($this->engine->plan($tenant, $now) as $decision) {
            $log = new ReminderLog(
                $tenant,
                $decision->userId,
                $decision->weekStart,
                $decision->channel,
                $decision->sequence,
                $decision->escalated,
                $now,
                $decision->escalated ? 'escalade_n1' : null,
            );
            $this->logs->save($log);
            $this->notifier->send($log);
        }
    }
}
