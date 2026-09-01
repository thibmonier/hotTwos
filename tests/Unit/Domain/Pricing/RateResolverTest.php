<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Pricing;

use App\Domain\Pricing\NoEffectiveRateException;
use App\Domain\Pricing\ProfileRate;
use App\Domain\Pricing\RateResolver;
use App\Domain\Shared\EffectivePeriod;
use App\Domain\Tenant\TenantId;
use App\Tests\Support\Pricing\InMemoryProfileRateRepository;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

/**
 * US-011 (T-011-03, ARC-6, INV-3) — le moteur résout le tarif **en vigueur à une date**
 * (`from <= date < to`), déterministe. Brique consommée par la valorisation (US-060).
 */
final class RateResolverTest extends TestCase
{
    private const string PROFILE = '018f9c4e-0000-7000-8000-0000000000cc';

    private TenantId $tenant;
    private InMemoryProfileRateRepository $rates;
    private RateResolver $resolver;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $this->rates = new InMemoryProfileRateRepository();
        $this->resolver = new RateResolver($this->rates);
    }

    public function testResolvesTheRateInEffectAtTheGivenDate(): void
    {
        $this->rates->save($this->rate(EffectivePeriod::between($this->date('2026-01-01'), $this->date('2026-04-01')), 40000));
        $this->rates->save($this->rate(EffectivePeriod::since($this->date('2026-04-01')), 45000));

        self::assertSame(40000, $this->resolver->resolveAt($this->tenant, self::PROFILE, $this->date('2026-02-15'))->costPriceCents());
        self::assertSame(45000, $this->resolver->resolveAt($this->tenant, self::PROFILE, $this->date('2026-05-10'))->costPriceCents());
    }

    public function testLowerBoundIsInclusive(): void
    {
        $this->rates->save($this->rate(EffectivePeriod::between($this->date('2026-01-01'), $this->date('2026-04-01')), 40000));

        self::assertSame(40000, $this->resolver->resolveAt($this->tenant, self::PROFILE, $this->date('2026-01-01'))->costPriceCents());
    }

    public function testUpperBoundIsExclusive(): void
    {
        $this->rates->save($this->rate(EffectivePeriod::between($this->date('2026-01-01'), $this->date('2026-04-01')), 40000));
        $this->rates->save($this->rate(EffectivePeriod::since($this->date('2026-04-01')), 45000));

        // Le 1er avril appartient à la seconde période (borne haute exclue).
        self::assertSame(45000, $this->resolver->resolveAt($this->tenant, self::PROFILE, $this->date('2026-04-01'))->costPriceCents());
    }

    public function testOpenEndedRateAppliesIndefinitely(): void
    {
        $this->rates->save($this->rate(EffectivePeriod::since($this->date('2026-01-01')), 45000));

        self::assertSame(45000, $this->resolver->resolveAt($this->tenant, self::PROFILE, $this->date('2099-12-31'))->costPriceCents());
    }

    public function testThrowsWhenNoRateInEffectAtTheDate(): void
    {
        $this->rates->save($this->rate(EffectivePeriod::between($this->date('2026-01-01'), $this->date('2026-04-01')), 40000));

        $this->expectException(NoEffectiveRateException::class);
        $this->resolver->resolveAt($this->tenant, self::PROFILE, $this->date('2025-12-31'));
    }

    public function testThrowsWhenProfileHasNoRateAtAll(): void
    {
        $this->expectException(NoEffectiveRateException::class);
        $this->resolver->resolveAt($this->tenant, self::PROFILE, $this->date('2026-01-01'));
    }

    private function rate(EffectivePeriod $period, int $costCents): ProfileRate
    {
        return new ProfileRate($this->tenant, self::PROFILE, $period, $costCents, $costCents * 2);
    }

    private function date(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value.' 00:00:00', new DateTimeZone('UTC'));
    }
}
