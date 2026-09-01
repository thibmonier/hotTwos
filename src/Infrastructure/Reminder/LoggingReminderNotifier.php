<?php

declare(strict_types=1);

namespace App\Infrastructure\Reminder;

use App\Domain\Reminder\ReminderLog;
use App\Domain\Reminder\ReminderNotifier;
use Psr\Log\LoggerInterface;

/**
 * Acheminement par journalisation (US-056). Implémentation de socle : trace l'intention de relance
 * sans média externe — la livraison effective in-app/email est une évolution suivie (dette). Aucune
 * donnée sensible n'est journalisée (identifiants techniques et rang uniquement — RGPD).
 */
final readonly class LoggingReminderNotifier implements ReminderNotifier
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function send(ReminderLog $reminder): void
    {
        $this->logger->info('Relance de complétude émise', [
            'tenant' => $reminder->tenantId()->toString(),
            'user' => $reminder->userId(),
            'week' => $reminder->weekStart()->format('Y-m-d'),
            'channel' => $reminder->channel()->value,
            'sequence' => $reminder->sequence(),
            'escalated' => $reminder->isEscalated(),
        ]);
    }
}
