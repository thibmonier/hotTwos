<?php

declare(strict_types=1);

namespace App\Domain\Project;

/**
 * Agrège l'avancement physique d'un projet à partir de ses lots (US-036) : moyenne **pondérée par la
 * charge budgétée** (`budgetDays`) des lots qui déclarent un avancement. Les lots sans avancement ou
 * sans charge budgétée sont ignorés. Renvoie `null` si aucun avancement exploitable (atterrissage
 * alors indisponible).
 */
final class ProjectProgressCalculator
{
    /**
     * @param iterable<ProjectLot> $lots
     */
    public function weightedPhysicalProgress(iterable $lots): ?int
    {
        $weightedSum = 0;
        $weightTotal = 0;
        foreach ($lots as $lot) {
            $progress = $lot->physicalProgressPercent();
            $weight = $lot->budgetDays();
            if (null === $progress || $weight <= 0) {
                continue;
            }
            $weightedSum += $progress * $weight;
            $weightTotal += $weight;
        }

        return $weightTotal > 0 ? (int) round($weightedSum / $weightTotal) : null;
    }
}
