<?php

declare(strict_types=1);

namespace App\Infrastructure\Valuation;

use App\Domain\Tenant\TenantId;
use App\Domain\Valuation\PeriodClosureStatus;

/**
 * Stub de clôture de période piloté par configuration (US-060, CA-5), en attendant US-057.
 *
 * Les périodes clôturées sont listées via `VALUATION_CLOSED_PERIODS` (mois `YYYY-MM` séparés
 * par des virgules). Le stub est volontairement tenant-agnostique : US-057 introduira une
 * clôture par tenant persistée en base. Tant que la variable est vide, aucune période n'est
 * clôturée (comportement par défaut sûr).
 */
final readonly class ConfiguredPeriodClosure implements PeriodClosureStatus
{
    /**
     * @param list<string> $closedPeriods
     */
    public function __construct(private array $closedPeriods)
    {
    }

    public function isClosed(TenantId $tenant, string $period): bool
    {
        // TEMPORAIRE (US-057) : clôture globale — tous les tenants voient les mêmes périodes.
        // US-057 persistera les clôtures par tenant et utilisera alors `$tenant`.
        return in_array($period, $this->closedPeriods, true);
    }
}
