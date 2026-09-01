<?php

declare(strict_types=1);

namespace App\Infrastructure\Valuation;

use App\Domain\Period\AccountingPeriodRepository;
use App\Domain\Tenant\TenantId;
use App\Domain\Valuation\PeriodClosureStatus;

/**
 * Statut de clôture réel (US-057) branché sur le port de valorisation `PeriodClosureStatus`
 * (US-060, CA-5). Remplace le stub `ConfiguredPeriodClosure` : la clôture est désormais lue sur
 * l'agrégat `AccountingPeriod` **par tenant** et par mois. Une période inconnue est ouverte.
 */
final readonly class DoctrinePeriodClosure implements PeriodClosureStatus
{
    public function __construct(private AccountingPeriodRepository $periods)
    {
    }

    public function isClosed(TenantId $tenant, string $period): bool
    {
        return $this->periods->findByPeriod($tenant, $period)?->isClosed() ?? false;
    }
}
