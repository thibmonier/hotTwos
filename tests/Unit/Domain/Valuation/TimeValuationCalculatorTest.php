<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Valuation;

use App\Domain\Valuation\TimeValuationCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * US-060 — conversion d'un tarif journalier (centimes) et d'une durée (minutes) en montant
 * d'imputation, en centimes entiers (arrondi au centime, jamais de flottant).
 */
final class TimeValuationCalculatorTest extends TestCase
{
    public function testFullDayYieldsTheDailyRate(): void
    {
        // 420 min = 1 jour ouvré standard (7 h).
        self::assertSame(45000, new TimeValuationCalculator()->entryCents(45000, 420));
    }

    public function testHalfDayYieldsHalfTheDailyRate(): void
    {
        self::assertSame(22500, new TimeValuationCalculator()->entryCents(45000, 210));
    }

    public function testRoundsToNearestCent(): void
    {
        // 45000 × 240 / 420 = 25714,285… → 25714.
        self::assertSame(25714, new TimeValuationCalculator()->entryCents(45000, 240));
    }

    public function testNegativeMinutesRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new TimeValuationCalculator()->entryCents(45000, -1);
    }
}
