<?php

declare(strict_types=1);

namespace App\Domain\Valuation;

use InvalidArgumentException;

/**
 * Conversion d'un tarif journalier en montant d'imputation (US-060).
 *
 * `montant = tarif journalier × minutes / minutes par jour ouvré`, en centimes entiers arrondis
 * (INV-2). Le jour ouvré standard vaut 420 minutes (7 h) — hypothèse métier documentée. Service
 * pur, sans dépendance framework.
 */
final class TimeValuationCalculator
{
    /** Durée d'un jour ouvré standard, en minutes (7 h). */
    public const int MINUTES_PER_DAY = 420;

    public function entryCents(int $dailyRateCents, int $minutes, int $minutesPerDay = self::MINUTES_PER_DAY): int
    {
        if ($dailyRateCents < 0) {
            throw new InvalidArgumentException('Le tarif journalier ne peut pas être négatif.');
        }
        if ($minutes < 0) {
            throw new InvalidArgumentException('La durée ne peut pas être négative.');
        }
        if ($minutesPerDay <= 0) {
            throw new InvalidArgumentException('Le nombre de minutes par jour doit être strictement positif.');
        }

        // Division entière arrondie au centime le plus proche (pas de flottant).
        return intdiv($dailyRateCents * $minutes + intdiv($minutesPerDay, 2), $minutesPerDay);
    }
}
