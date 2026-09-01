<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Shared;

use App\Domain\Shared\EffectivePeriod;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use DateTimeZone;

/**
 * US-010 / US-011 — période de validité à date d'effet (risque n°1 du Sprint 4).
 *
 * Intervalle semi-ouvert [from, to) : borne basse incluse, borne haute exclue
 * (aligné sur la résolution du tarif en vigueur, RateResolver `from <= date < to`).
 * `to = null` signifie « en cours » (pas de fin).
 */
final class EffectivePeriodTest extends TestCase
{
    public function testSinceCreatesAnOpenEndedPeriod(): void
    {
        $period = EffectivePeriod::since($this->date('2026-01-01'));

        self::assertTrue($period->isOpenEnded());
        self::assertNull($period->to());
        self::assertEquals($this->date('2026-01-01'), $period->from());
    }

    public function testBetweenCreatesABoundedPeriod(): void
    {
        $period = EffectivePeriod::between($this->date('2026-01-01'), $this->date('2026-04-01'));

        self::assertFalse($period->isOpenEnded());
        self::assertEquals($this->date('2026-04-01'), $period->to());
    }

    public function testBetweenRejectsAnEmptyPeriodWhenToEqualsFrom(): void
    {
        $this->expectException(InvalidArgumentException::class);

        EffectivePeriod::between($this->date('2026-01-01'), $this->date('2026-01-01'));
    }

    public function testBetweenRejectsAnInvertedPeriodWhenToPrecedesFrom(): void
    {
        $this->expectException(InvalidArgumentException::class);

        EffectivePeriod::between($this->date('2026-04-01'), $this->date('2026-01-01'));
    }

    public function testContainsIncludesTheLowerBound(): void
    {
        $period = EffectivePeriod::between($this->date('2026-01-01'), $this->date('2026-04-01'));

        self::assertTrue($period->contains($this->date('2026-01-01')));
    }

    public function testContainsExcludesTheUpperBound(): void
    {
        $period = EffectivePeriod::between($this->date('2026-01-01'), $this->date('2026-04-01'));

        self::assertFalse($period->contains($this->date('2026-04-01')));
    }

    public function testContainsRejectsADateBeforeTheLowerBound(): void
    {
        $period = EffectivePeriod::between($this->date('2026-01-01'), $this->date('2026-04-01'));

        self::assertFalse($period->contains($this->date('2025-12-31')));
    }

    public function testOpenEndedPeriodContainsAnyDateFromTheLowerBound(): void
    {
        $period = EffectivePeriod::since($this->date('2026-01-01'));

        self::assertTrue($period->contains($this->date('2026-01-01')));
        self::assertTrue($period->contains($this->date('2099-12-31')));
        self::assertFalse($period->contains($this->date('2025-12-31')));
    }

    public function testAdjacentPeriodsDoNotOverlap(): void
    {
        // [jan, avr) et [avr, juil) se touchent en avril mais ne se chevauchent pas (borne haute exclue).
        $first = EffectivePeriod::between($this->date('2026-01-01'), $this->date('2026-04-01'));
        $second = EffectivePeriod::between($this->date('2026-04-01'), $this->date('2026-07-01'));

        self::assertFalse($first->overlaps($second));
        self::assertFalse($second->overlaps($first));
    }

    public function testPartiallyCoveringPeriodsOverlap(): void
    {
        $first = EffectivePeriod::between($this->date('2026-01-01'), $this->date('2026-04-01'));
        $second = EffectivePeriod::between($this->date('2026-03-01'), $this->date('2026-06-01'));

        self::assertTrue($first->overlaps($second));
        self::assertTrue($second->overlaps($first));
    }

    public function testOpenEndedPeriodOverlapsAnyLaterPeriod(): void
    {
        $open = EffectivePeriod::since($this->date('2026-01-01'));
        $later = EffectivePeriod::between($this->date('2030-01-01'), $this->date('2030-02-01'));

        self::assertTrue($open->overlaps($later));
        self::assertTrue($later->overlaps($open));
    }

    public function testOpenEndedPeriodDoesNotOverlapAnEarlierClosedPeriod(): void
    {
        $open = EffectivePeriod::since($this->date('2026-06-01'));
        $earlier = EffectivePeriod::between($this->date('2026-01-01'), $this->date('2026-06-01'));

        self::assertFalse($open->overlaps($earlier));
        self::assertFalse($earlier->overlaps($open));
    }

    public function testEqualsComparesBothBounds(): void
    {
        $a = EffectivePeriod::between($this->date('2026-01-01'), $this->date('2026-04-01'));
        $b = EffectivePeriod::between($this->date('2026-01-01'), $this->date('2026-04-01'));
        $c = EffectivePeriod::since($this->date('2026-01-01'));

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
        self::assertTrue($c->equals(EffectivePeriod::since($this->date('2026-01-01'))));
    }

    private function date(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value.' 00:00:00', new DateTimeZone('UTC'));
    }
}
