<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Calendar;

use App\Domain\Calendar\EasterCalculator;
use PHPUnit\Framework\TestCase;

/**
 * US-023 (EF-REF-6) — comput grégorien de Pâques et fériés mobiles dérivés.
 */
final class EasterCalculatorTest extends TestCase
{
    public function testEasterSundayKnownYears(): void
    {
        self::assertSame('2027-03-28', EasterCalculator::easterSunday(2027)->format('Y-m-d'));
        self::assertSame('2026-04-05', EasterCalculator::easterSunday(2026)->format('Y-m-d'));
        self::assertSame('2024-03-31', EasterCalculator::easterSunday(2024)->format('Y-m-d'));
    }

    public function testMobileHolidaysDerivedFromEaster(): void
    {
        $mobiles = EasterCalculator::mobileHolidays(2027);

        // Pâques 2027 = 28/03 → Lundi de Pâques 29/03, Ascension 06/05, Lundi de Pentecôte 17/05.
        self::assertArrayHasKey('2027-03-29', $mobiles);
        self::assertArrayHasKey('2027-05-06', $mobiles);
        self::assertArrayHasKey('2027-05-17', $mobiles);
        self::assertSame('Lundi de Pâques', $mobiles['2027-03-29']);
        self::assertSame('Ascension', $mobiles['2027-05-06']);
        self::assertSame('Lundi de Pentecôte', $mobiles['2027-05-17']);
    }
}
