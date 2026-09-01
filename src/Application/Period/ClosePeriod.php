<?php

declare(strict_types=1);

namespace App\Application\Period;

use App\Application\Authorization\Authorizer;
use App\Application\Period\Message\PeriodClosed;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\SecurityAuditLogger;
use App\Domain\Period\AccountingPeriod;
use App\Domain\Period\AccountingPeriodRepository;
use App\Domain\Period\PeriodException;
use App\Domain\Shared\CalendarMonth;
use App\Domain\Tenant\TenantId;
use App\Domain\Timesheet\TimeEntryRepository;
use App\Domain\User\User;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Clôture d'une période comptable (US-057, CA-1/CA-3).
 *
 * Réservé à un rôle habilité (`MANAGE_PERIODS` → 403). Verrouille la période : les imputations
 * deviennent non modifiables (INV-7, le verrou dérive du statut de la période — voir la vérification
 * en écriture). Si des imputations ne sont pas finalisées (CA-3), la clôture est refusée (422) sauf
 * confirmation explicite `force` (« clôturer malgré tout », tracée). Publie {@see PeriodClosed}
 * (async) pour déclencher les calculs aval (valorisation…). Journalise `periode_cloturee` (HAB-6).
 */
final readonly class ClosePeriod
{
    private const string PERIOD_FORMAT = '/^\d{4}-(0[1-9]|1[0-2])$/';

    public function __construct(
        private Authorizer $authorizer,
        private AccountingPeriodRepository $periods,
        private TimeEntryRepository $entries,
        private SecurityAuditLogger $audit,
        private MessageBusInterface $bus,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @param string $period mois au format YYYY-MM (validé)
     *
     * @return int nombre d'imputations non finalisées exclues de la clôture (0 en clôture propre)
     */
    public function close(TenantId $tenant, User $actor, string $period, bool $force = false): int
    {
        $this->authorizer->ensureCan($actor, Permission::MANAGE_PERIODS);

        if (1 !== preg_match(self::PERIOD_FORMAT, $period)) {
            throw new PeriodException(sprintf('Période invalide « %s » (attendu YYYY-MM).', $period));
        }

        $existing = $this->periods->findByPeriod($tenant, $period);
        if ($existing instanceof AccountingPeriod && $existing->isClosed()) {
            throw new PeriodException(sprintf('La période %s est déjà clôturée.', $period));
        }

        [$from, $to] = CalendarMonth::bounds($period);
        $unvalidated = $this->entries->countUnvalidatedInPeriod($tenant, $from, $to);
        if ($unvalidated > 0 && !$force) {
            throw new PeriodException(sprintf('%d imputation(s) non finalisée(s) sur %s. Confirmez pour clôturer malgré tout.', $unvalidated, $period));
        }

        $accountingPeriod = $existing ?? new AccountingPeriod($tenant, $period);
        $accountingPeriod->close($actor->id(), $this->clock->now());
        $this->periods->save($accountingPeriod);

        $this->audit->record('periode_cloturee', $tenant->toString(), $actor->getUserIdentifier(), [
            'period' => $period,
            'forced' => $force ? '1' : '0',
            'unvalidated_excluded' => (string) $unvalidated,
        ]);

        // Calculs aval asynchrones (valorisation, facturation…).
        $this->bus->dispatch(new PeriodClosed($tenant->toString(), $period));

        return $unvalidated;
    }
}
