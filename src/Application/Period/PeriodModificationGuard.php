<?php

declare(strict_types=1);

namespace App\Application\Period;

use App\Domain\Authorization\SecurityAuditLogger;
use App\Domain\Period\AccountingPeriodRepository;
use App\Domain\Period\PeriodLockedException;
use App\Domain\Tenant\TenantId;
use DateTimeImmutable;

/**
 * Garde de modification des imputations vis-à-vis de la clôture de période (US-057, CA-4, INV-7).
 *
 * Toute écriture sur une imputation dont le mois est clôturé est refusée (**423**) — le verrou
 * dérive du statut de la période (pas d'un 4ᵉ statut sur l'imputation). La tentative est tracée
 * (HAB-6). Une réouverture formelle active lèvera ce verrou (T-057-05).
 */
final readonly class PeriodModificationGuard
{
    public function __construct(
        private AccountingPeriodRepository $periods,
        private SecurityAuditLogger $audit,
    ) {
    }

    public function ensureModifiable(TenantId $tenant, string $actorId, DateTimeImmutable $workDate): void
    {
        $period = $workDate->format('Y-m');
        $accountingPeriod = $this->periods->findByPeriod($tenant, $period);
        if (!$accountingPeriod instanceof \App\Domain\Period\AccountingPeriod || !$accountingPeriod->isClosed()) {
            return;
        }

        $this->audit->record('tentative_modification_periode_cloturee', $tenant->toString(), $actorId, [
            'period' => $period,
        ]);

        throw new PeriodLockedException(sprintf('Cette imputation appartient à une période clôturée (%s). Demandez une réouverture formelle.', $period));
    }
}
