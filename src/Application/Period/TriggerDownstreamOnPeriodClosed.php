<?php

declare(strict_types=1);

namespace App\Application\Period;

use App\Application\Period\Message\PeriodClosed;
use App\Application\Timesheet\Message\TimeEntriesValidated;
use App\Domain\Shared\CalendarMonth;
use App\Domain\Timesheet\TimeEntry;
use App\Domain\Timesheet\TimeEntryRepository;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Calculs aval à la clôture d'une période (US-057, CA-1).
 *
 * À la consommation de {@see PeriodClosed}, ré-émet la validation des imputations **validées** du
 * mois afin de (re)déclencher leur valorisation ({@see \App\Application\Valuation\ValueValidatedTimeHandler}).
 * Handler tenant-aware (contexte posé par le middleware à la consommation).
 */
#[AsMessageHandler]
final readonly class TriggerDownstreamOnPeriodClosed
{
    public function __construct(
        private TimeEntryRepository $entries,
        private MessageBusInterface $bus,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(PeriodClosed $message): void
    {
        $tenant = $message->tenantId();
        [$from, $to] = CalendarMonth::bounds($message->period());

        $entryIds = array_map(
            static fn (TimeEntry $entry): string => $entry->id(),
            $this->entries->findValidatedInPeriod($tenant, $from, $to),
        );

        if ([] !== $entryIds) {
            $this->bus->dispatch(new TimeEntriesValidated($tenant->toString(), $entryIds, $this->clock->now()));
        }
    }
}
