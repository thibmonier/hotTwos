<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Utilitaire de mois calendaire `YYYY-MM` (US-057/US-060) — **source unique** de la validation du
 * format et des bornes semi-ouvertes `[1er jour du mois, 1er jour du mois suivant)`, en UTC.
 */
final class CalendarMonth
{
    /** Format canonique d'un mois calendaire. */
    public const string PATTERN = '/^\d{4}-(0[1-9]|1[0-2])$/';

    /**
     * @phpstan-assert-if-true non-empty-string $period
     */
    public static function isValid(string $period): bool
    {
        return 1 === preg_match(self::PATTERN, $period);
    }

    /**
     * @return array{DateTimeImmutable, DateTimeImmutable} bornes semi-ouvertes du mois
     *
     * @throws InvalidArgumentException si le format n'est pas `YYYY-MM`
     */
    public static function bounds(string $period): array
    {
        if (!self::isValid($period)) {
            throw new InvalidArgumentException(sprintf('Période invalide « %s » (attendu YYYY-MM).', $period));
        }

        $from = new DateTimeImmutable($period.'-01 00:00:00', new DateTimeZone('UTC'));

        return [$from, $from->modify('+1 month')];
    }
}
