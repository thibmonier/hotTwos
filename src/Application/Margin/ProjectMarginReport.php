<?php

declare(strict_types=1);

namespace App\Application\Margin;

/**
 * Rapport de marge d'une période, consolidé et gated (US-071, T-071-06).
 *
 * `costVisible` reflète l'habilitation coût (HAB-1) du lecteur : quand elle est fausse, marge
 * consolidée et coûts par ligne sont masqués (`null`). `hasPartial` signale globalement la présence
 * d'au moins une marge partielle (valorisation incomplète, CA-4).
 */
final readonly class ProjectMarginReport
{
    /**
     * @param list<ProjectMarginRow> $rows
     */
    public function __construct(
        public string $period,
        public array $rows,
        public bool $costVisible,
        public int $totalRevenueCents,
        public ?int $totalMarginCents,
        public bool $hasPartial,
    ) {
    }
}
