<?php

declare(strict_types=1);

namespace App\Application\Margin;

use App\Domain\Margin\ProjectMargin;
use App\Domain\Margin\ProjectMarginRepository;
use App\Domain\Shared\CalendarMonth;
use App\Domain\Tenant\TenantId;
use App\Domain\Valuation\ProjectValuationLine;
use App\Domain\Valuation\TimeEntryValuationRepository;
use Psr\Clock\ClockInterface;

/**
 * Moteur de figeage des marges par projet à la clôture d'une période (US-071, T-071-04, CA-1).
 *
 * Agrège, sur le mois clôturé, le CA reconnu et le coût valorisé par projet (depuis les snapshots
 * figés {@see \App\Domain\Valuation\TimeEntryValuation}, source unique du CA reconnu — ADR-0020) et
 * **fige** la marge (CA − coût) par projet dans {@see ProjectMargin}. Non-rétroactivité (INV-2) :
 * seules les marges de la période visée sont remplacées, jamais celles des autres périodes. Une
 * valorisation incomplète (imputations `MISSING_RATE`, CA-4) marque la ligne « partielle » et en
 * indique le volume.
 */
final readonly class ComputeProjectMargins
{
    public function __construct(
        private TimeEntryValuationRepository $valuations,
        private ProjectMarginRepository $margins,
        private ClockInterface $clock,
    ) {
    }

    public function forClosedPeriod(TenantId $tenant, string $period): void
    {
        [$from, $to] = CalendarMonth::bounds($period);

        $breakdown = $this->valuations->projectBreakdownForPeriod($tenant, $from, $to);
        $missingByProject = $this->valuations->missingRateCountByProjectForPeriod($tenant, $from, $to);
        $frozenAt = $this->clock->now();

        $margins = array_map(
            fn (ProjectValuationLine $line): ProjectMargin => ProjectMargin::freeze(
                $tenant,
                $period,
                $line->projectId,
                $line->projectName,
                $line->revenueCents,
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
