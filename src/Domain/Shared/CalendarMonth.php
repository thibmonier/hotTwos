<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Utilitaire de mois calendaire `YYYY-MM` (US-057/US-060) — source unique des bornes semi-ouvertes
 * `[1er jour du mois, 1er jour du mois suivant)`, en UTC.
 */
final class CalendarMonth
{
    /**
     * @return array{DateTimeImmutable, DateTimeImmutable} bornes semi-ouvertes du mois
     */
    public static function bounds(string $period): array
    {
        $from = new DateTimeImmutable($period.'-01 00:00:00', new DateTimeZone('UTC'));

        return [$from, $from->modify('+1 month')];
    }
}
