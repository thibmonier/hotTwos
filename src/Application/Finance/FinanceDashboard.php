<?php

declare(strict_types=1);

namespace App\Application\Finance;

/**
 * Tableau de bord finance consolidé d'une période (US-073), **déjà gated**.
 *
 * Totaux tenant + ventilations par client et par projet, tous en centimes entiers. Coût, marge et
 * comptage des projets en dérive sont `null` sans habilitation coût (HAB-1) ; le CA reste visible.
 * `hasData` distingue une période sans marges figées (non clôturée / provisoire).
 */
final readonly class FinanceDashboard
{
    /**
     * @param list<string>             $availablePeriods périodes disposant de marges figées (récent → ancien)
     * @param list<FinanceClientLine>  $byClient
     * @param list<FinanceProjectLine> $byProject
     */
    public function __construct(
        public ?string $period,
        public bool $hasData,
        public bool $costVisible,
        public array $availablePeriods,
        public ?string $clientFilter,
        public int $totalRevenueCents,
        public ?int $totalCostCents,
        public ?int $totalMarginCents,
        public ?float $totalMarginRatePercent,
        public int $projectCount,
        public ?int $driftingProjectCount,
        public bool $hasPartial,
        public array $byClient,
        public array $byProject,
    ) {
    }
}
