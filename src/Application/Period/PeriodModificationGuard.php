<?php

declare(strict_types=1);

namespace App\Application\Period;

use App\Domain\Authorization\SecurityAuditLogger;
use App\Domain\Period\AccountingPeriod;
use App\Domain\Period\AccountingPeriodRepository;
use App\Domain\Period\PeriodLockedException;
use App\Domain\Period\ReopeningRequestRepository;
use App\Domain\Tenant\TenantId;
use DateTimeImmutable;
use Psr\Clock\ClockInterface;

/**
 * Garde de modification des imputations vis-à-vis de la clôture de période (US-057, CA-4, INV-7).
 *
 * Toute écriture sur une imputation dont le mois est clôturé est refusée (**423**) — sauf pendant
 * une **réouverture formelle active** (approuvée, non expirée — CA-2), qui rouvre temporairement la
 * fenêtre de modification. Le verrou dérive du statut de la période (pas d'un 4ᵉ statut sur
 * l'imputation). La tentative bloquée est tracée (HAB-6).
 */
final readonly class PeriodModificationGuard
{
    public function __construct(
        private AccountingPeriodRepository $periods,
        private ReopeningRequestRepository $reopenings,
        private SecurityAuditLogger $audit,
        private ClockInterface $clock,
    ) {
    }

    public function ensureModifiable(TenantId $tenant, string $actorId, DateTimeImmutable $workDate): void
    {
        $period = $workDate->format('Y-m');
        $accountingPeriod = $this->periods->findByPeriod($tenant, $period);
        if (!$accountingPeriod instanceof AccountingPeriod || !$accountingPeriod->isClosed()) {
            return;
        }

        // Une réouverture formelle active lève le verrou (CA-2).
        if ($this->reopenings->findActiveForPeriod($tenant, $period, $this->clock->now()) instanceof \App\Domain\Period\ReopeningRequest) {
            return;
        }

        $this->audit->record('tentative_modification_periode_cloturee', $tenant->toString(), $actorId, [
            'period' => $period,
        ]);

        throw new PeriodLockedException(sprintf('Cette imputation appartient à une période clôturée (%s). Demandez une réouverture formelle.', $period));
    }
}
