<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

use InvalidArgumentException;

/**
 * Calcul du coût de revient « chargé » journalier (US-011, CA-2).
 *
 * `coût journalier = brut annuel × (1 + taux de charge) / jours ouvrés`, en **centimes entiers**
 * (INV-2). Le taux de charge est exprimé en points de base (bp : 4500 = 45 %) pour rester en
 * arithmétique entière. Service pur, sans dépendance framework.
 */
final class LoadedCostCalculator
{
    private const int BASIS_POINTS = 10_000;
    private const int DEFAULT_WORKING_DAYS = 218;

    public function dailyCostCents(int $annualGrossCents, int $chargeRateBasisPoints, int $workingDays = self::DEFAULT_WORKING_DAYS): int
    {
        if ($annualGrossCents < 0) {
            throw new InvalidArgumentException('Le brut annuel ne peut pas être négatif.');
        }
        if ($chargeRateBasisPoints < 0) {
            throw new InvalidArgumentException('Le taux de charge ne peut pas être négatif.');
        }
        if ($workingDays <= 0) {
            throw new InvalidArgumentException('Le nombre de jours ouvrés doit être strictement positif.');
        }

        $loadedAnnual = $annualGrossCents * (self::BASIS_POINTS + $chargeRateBasisPoints);
        $denominator = self::BASIS_POINTS * $workingDays;

        // Division entière arrondie au centime le plus proche (pas de flottant).
        return intdiv($loadedAnnual + intdiv($denominator, 2), $denominator);
    }
}
