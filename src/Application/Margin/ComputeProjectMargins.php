<?php

declare(strict_types=1);

namespace App\Application\Margin;

use App\Domain\Margin\ProjectMargin;
use App\Domain\Margin\ProjectMarginRepository;
use App\Domain\Margin\RevenueSource;
use App\Domain\Shared\CalendarMonth;
use App\Domain\Tenant\TenantId;
use App\Domain\Valuation\ProjectValuationLine;
use App\Domain\Valuation\TimeEntryValuationRepository;
use Psr\Clock\ClockInterface;

/**
 * Moteur de figeage des marges par projet à la clôture d'une période (US-071, T-071-04, CA-1).
 *
 * Agrège, sur le mois clôturé, le revenu retenu ({@see RevenueSource} — facturé réel s'il existe,
 * sinon CA reconnu, ADR-0022) et le coût valorisé par projet, et **fige** la marge par projet dans
 * {@see ProjectMargin}. Réexécutable (idempotent) : une émission de facture (US-075) re-fige la marge
 * de la période avec le facturé réel. Non-rétroactivité (INV-2) sur les changements de taux :
 * seules les marges de la période visée sont remplacées, jamais celles des autres périodes. Une
 * valorisation incomplète (imputations `MISSING_RATE`, CA-4) marque la ligne « partielle » et en
 * indique le volume.
 */
final readonly class ComputeProjectMargins
{
    public function __construct(
        private TimeEntryValuationRepository $valuations,
        private ProjectMarginRepository $margins,
        private RevenueSource $revenueSource,
        private ClockInterface $clock,
    ) {
    }

    public function forClosedPeriod(TenantId $tenant, string $period): void
    {
        [$from, $to] = CalendarMonth::bounds($period);

        $breakdown = $this->valuations->projectBreakdownForPeriod($tenant, $from, $to);
        $missingByProject = $this->valuations->missingRateCountByProjectForPeriod($tenant, $from, $to);
        $frozenAt = $this->clock->now();

        // Revenu retenu = facturé réel s'il existe, sinon CA reconnu (ADR-0022, source unique US-076).
        $margins = array_map(
            fn (ProjectValuationLine $line): ProjectMargin => ProjectMargin::freeze(
                $tenant,
                $period,
                $line->projectId,
                $line->projectName,
                $this->revenueSource->revenueFor($tenant, $line->projectId, $period, $line->revenueCents),
                $line->costCents,
                $line->valuedCount,
                $missingByProject[$line->projectId] ?? 0,
                $frozenAt,
            ),
            $breakdown,
        );

        $this->margins->replaceForPeriod($tenant, $period, $margins);
    }
}
