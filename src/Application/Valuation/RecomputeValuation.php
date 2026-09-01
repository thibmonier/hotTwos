<?php

declare(strict_types=1);

namespace App\Application\Valuation;

use App\Application\Authorization\Authorizer;
use App\Application\Timesheet\Message\TimeEntriesValidated;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\SecurityAuditLogger;
use App\Domain\Tenant\TenantId;
use App\Domain\Timesheet\TimeEntry;
use App\Domain\Timesheet\TimeEntryRepository;
use App\Domain\User\User;
use App\Domain\Valuation\PeriodClosedException;
use App\Domain\Valuation\PeriodClosureStatus;
use App\Domain\Valuation\ValuationException;
use DateTimeImmutable;
use DateTimeZone;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Recalcul manuel de la valorisation d'une période (US-060, CA-5).
 *
 * Réservé à un rôle habilité (RECOMPUTE_VALUATION → 403). Une période clôturée verrouille le
 * recalcul ({@see PeriodClosedException} → 423) tant qu'une réouverture formelle (US-057) n'est
 * pas accordée. Sur période ouverte, ré-émet un {@see TimeEntriesValidated} pour les imputations
 * validées du mois — {@see ValueValidatedTimeHandler} les re-valorise avec le tarif en vigueur —
 * et trace l'opération (auteur, période, périmètre) dans le journal d'audit (HAB-6).
 */
final readonly class RecomputeValuation
{
    private const string PERIOD_FORMAT = '/^\d{4}-(0[1-9]|1[0-2])$/';

    public function __construct(
        private Authorizer $authorizer,
        private PeriodClosureStatus $closure,
        private TimeEntryRepository $entries,
        private SecurityAuditLogger $audit,
        private MessageBusInterface $bus,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @param non-empty-string $period mois au format YYYY-MM
     *
     * @return int nombre d'imputations soumises au recalcul
     */
    public function forPeriod(TenantId $tenant, User $actor, string $period): int
    {
        $this->authorizer->ensureCan($actor, Permission::RECOMPUTE_VALUATION);

        if (1 !== preg_match(self::PERIOD_FORMAT, $period)) {
            throw new ValuationException(sprintf('Période invalide « %s » (attendu YYYY-MM).', $period));
        }

        if ($this->closure->isClosed($tenant, $period)) {
            $this->audit->record('valuation_recompute_blocked_closed_period', $tenant->toString(), $actor->getUserIdentifier(), ['period' => $period]);

            throw new PeriodClosedException(sprintf('La période %s est clôturée — recalcul impossible sans réouverture formelle (US-057).', $this->label($period)));
        }

        [$from, $to] = $this->monthBounds($period);
        $entryIds = array_map(
            static fn (TimeEntry $entry): string => $entry->id(),
            $this->entries->findValidatedInPeriod($tenant, $from, $to),
        );

        if ([] !== $entryIds) {
            $this->bus->dispatch(new TimeEntriesValidated($tenant->toString(), $entryIds, $this->clock->now()));
        }

        $this->audit->record('valuation_recomputed', $tenant->toString(), $actor->getUserIdentifier(), [
            'period' => $period,
            'count' => (string) count($entryIds),
        ]);

        return count($entryIds);
    }

    /**
     * @return array{DateTimeImmutable, DateTimeImmutable} bornes [premier jour du mois, premier jour du mois suivant)
     */
    private function monthBounds(string $period): array
    {
        $from = new DateTimeImmutable($period.'-01 00:00:00', new DateTimeZone('UTC'));

        return [$from, $from->modify('+1 month')];
    }

    private function label(string $period): string
    {
        $months = ['01' => 'Janvier', '02' => 'Février', '03' => 'Mars', '04' => 'Avril', '05' => 'Mai', '06' => 'Juin', '07' => 'Juillet', '08' => 'Août', '09' => 'Septembre', '10' => 'Octobre', '11' => 'Novembre', '12' => 'Décembre'];
        [$year, $month] = explode('-', $period);

        return sprintf('%s %s', $months[$month] ?? $month, $year);
    }
}
