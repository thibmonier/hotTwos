<?php

declare(strict_types=1);

namespace App\Application\Valuation;

use App\Application\Pricing\Message\ProfileRateDefined;
use App\Application\Timesheet\Message\TimeEntriesValidated;
use App\Domain\Valuation\TimeEntryValuation;
use App\Domain\Valuation\TimeEntryValuationRepository;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Re-déclenchement automatique de la valorisation à la définition d'un tarif (US-060, CA-4).
 *
 * À la consommation de {@see ProfileRateDefined}, ré-émet un {@see TimeEntriesValidated} pour
 * les imputations restées `missing_rate` : {@see ValueValidatedTimeHandler} les re-valorise
 * avec le tarif désormais disponible. Celles dont le profil ne correspond pas au tarif ajouté
 * restent simplement `missing_rate`. Le supersede par imputation source (T-060-04) garantit
 * l'absence de double comptage du CA reconnu.
 */
#[AsMessageHandler]
final readonly class RevalueOnRateDefinedHandler
{
    public function __construct(
        private TimeEntryValuationRepository $valuations,
        private MessageBusInterface $bus,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(ProfileRateDefined $message): void
    {
        $tenant = $message->tenantId();

        $entryIds = array_map(
            static fn (TimeEntryValuation $valuation): string => $valuation->timeEntryId(),
            $this->valuations->findMissingRate($tenant),
        );

        if ([] === $entryIds) {
            return;
        }

        $this->bus->dispatch(new TimeEntriesValidated($tenant->toString(), $entryIds, $this->clock->now()));
    }
}
