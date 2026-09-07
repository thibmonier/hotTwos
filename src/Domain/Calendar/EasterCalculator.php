<?php

declare(strict_types=1);

namespace App\Domain\Calendar;

use DateTimeImmutable;
use DateTimeZone;

/**
 * US-023 (EF-REF-6) — calcul du dimanche de Pâques (comput grégorien, algorithme de Butcher) et des
 * fériés mobiles français dérivés. Fonction pure, testable hors base.
 */
final class EasterCalculator
{
    /** Dimanche de Pâques (grégorien) pour l'année donnée. */
    public static function easterSunday(int $year): DateTimeImmutable
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day), new DateTimeZone('UTC'));
    }

    /**
     * Fériés mobiles français de l'année : Lundi de Pâques (+1), Ascension (+39), Lundi de Pentecôte (+50).
     *
     * @return array<string, string> date 'Y-m-d' => libellé
     */
    public static function mobileHolidays(int $year): array
    {
        $easter = self::easterSunday($year);

        return [
            $easter->modify('+1 day')->format('Y-m-d') => 'Lundi de Pâques',
            $easter->modify('+39 days')->format('Y-m-d') => 'Ascension',
            $easter->modify('+50 days')->format('Y-m-d') => 'Lundi de Pentecôte',
        ];
    }
}
