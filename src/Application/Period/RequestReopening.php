<?php

declare(strict_types=1);

namespace App\Application\Period;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\SecurityAuditLogger;
use App\Domain\Period\AccountingPeriod;
use App\Domain\Period\PeriodException;
use App\Domain\Period\ReopeningRequest;
use App\Domain\Period\ReopeningRequestRepository;
use App\Domain\Period\AccountingPeriodRepository;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use Psr\Clock\ClockInterface;

/**
 * Demande de réouverture d'une période clôturée (US-057, CA-2/CA-5).
 *
 * Réservé à un rôle habilité (`REQUEST_PERIOD_REOPENING` → 403, CA-5). Un motif est obligatoire.
 * La demande ne vaut que sur une période effectivement clôturée. Tracée (HAB-6).
 */
final readonly class RequestReopening
{
    public function __construct(
        private Authorizer $authorizer,
        private AccountingPeriodRepository $periods,
        private ReopeningRequestRepository $reopenings,
        private SecurityAuditLogger $audit,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @param non-empty-string $period mois au format YYYY-MM
     *
     * @return string identifiant de la demande créée
     */
    public function request(TenantId $tenant, User $actor, string $period, string $reason): string
    {
        $this->authorizer->ensureCan($actor, Permission::REQUEST_PERIOD_REOPENING);

        if ('' === trim($reason)) {
            throw new PeriodException('Un motif est obligatoire pour demander une réouverture.');
        }

        $accountingPeriod = $this->periods->findByPeriod($tenant, $period);
        if (!$accountingPeriod instanceof AccountingPeriod || !$accountingPeriod->isClosed()) {
            throw new PeriodException(sprintf('La période %s n\'est pas clôturée : aucune réouverture nécessaire.', $period));
        }

        // Pas de demande redondante quand une réouverture est déjà active (évite l'« approval fatigue »).
        if ($this->reopenings->findActiveForPeriod($tenant, $period, $this->clock->now()) instanceof ReopeningRequest) {
            throw new PeriodException(sprintf('Une réouverture est déjà active sur la période %s.', $period));
        }

        $request = new ReopeningRequest($tenant, $period, $actor->id(), trim($reason), $this->clock->now());
        $this->reopenings->save($request);

        $this->audit->record('reouverture_demandee', $tenant->toString(), $actor->getUserIdentifier(), [
            'period' => $period,
            'request' => $request->id(),
        ]);

        return $request->id();
    }
}
