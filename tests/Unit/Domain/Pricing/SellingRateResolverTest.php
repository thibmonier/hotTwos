<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Pricing;

use App\Domain\Pricing\NoEffectiveRateException;
use App\Domain\Pricing\ProfileRate;
use App\Domain\Pricing\RateResolver;
use App\Domain\Pricing\RateScope;
use App\Domain\Pricing\SellingRate;
use App\Domain\Pricing\SellingRateResolver;
use App\Domain\Shared\EffectivePeriod;
use App\Domain\Tenant\TenantId;
use App\Tests\Support\Pricing\InMemoryProfileRateRepository;
use App\Tests\Support\Pricing\InMemorySellingRateRepository;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

/**
 * US-015 (EF-REF-19, CA-1..CA-4) — priorité projet > client > profil, repli sur le taux profil,
 * historisation à date d'effet (INV-2).
 */
final class SellingRateResolverTest extends TestCase
{
    private const string PROFILE = '018f9c4e-0000-7000-8000-0000000000c1';
    private const string CLIENT = '018f9c4e-0000-7000-8000-0000000000d1';
    private const string PROJECT = '018f9c4e-0000-7000-8000-0000000000e1';

    private TenantId $tenant;
    private InMemoryProfileRateRepository $profileRates;
    private InMemorySellingRateRepository $overrides;
    private SellingRateResolver $resolver;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $this->profileRates = new InMemoryProfileRateRepository();
        $this->overrides = new InMemorySellingRateRepository();
        $this->resolver = new SellingRateResolver($this->overrides, new RateResolver($this->profileRates));

        // Taux profil par défaut : 800 €/j (coût 500).
        $this->profileRates->save(new ProfileRate($this->tenant, self::PROFILE, EffectivePeriod::since($this->d('2026-01-01')), 500_00, 800_00));
    }

    public function testFallsBackToProfileRate(): void
    {
        $resolved = $this->resolver->resolve($this->tenant, self::PROFILE, self::CLIENT, self::PROJECT, $this->d('2027-02-01'));

        self::assertSame(800_00, $resolved->sellingPriceCents);
        self::assertSame('profil', $resolved->level);
    }

    public function testProjectOverrideWinsOverClientAndProfile(): void
    {
        $this->overrides->save(new SellingRate($this->tenant, self::PROFILE, RateScope::CLIENT, self::CLIENT, EffectivePeriod::since($this->d('2026-01-01')), 850_00));
        $this->overrides->save(new SellingRate($this->tenant, self::PROFILE, RateScope::PROJECT, self::PROJECT, EffectivePeriod::since($this->d('2026-01-01')), 900_00));

        $resolved = $this->resolver->resolve($this->tenant, self::PROFILE, self::CLIENT, self::PROJECT, $this->d('2027-02-01'));

        self::assertSame(900_00, $resolved->sellingPriceCents);
        self::assertSame('projet', $resolved->level);
    }

    public function testClientOverrideWhenNoProjectRate(): void
    {
        $this->overrides->save(new SellingRate($this->tenant, self::PROFILE, RateScope::CLIENT, self::CLIENT, EffectivePeriod::since($this->d('2026-01-01')), 850_00));

        $resolved = $this->resolver->resolve($this->tenant, self::PROFILE, self::CLIENT, self::PROJECT, $this->d('2027-02-01'));

        self::assertSame(850_00, $resolved->sellingPriceCents);
        self::assertSame('client', $resolved->level);
    }

    public function testUsesOverrideEffectiveAtDate(): void
    {
        // Taux projet historisé : 900 jusqu'au 01/07, puis 950.
        $this->overrides->save(new SellingRate($this->tenant, self::PROFILE, RateScope::PROJECT, self::PROJECT, EffectivePeriod::between($this->d('2026-01-01'), $this->d('2026-07-01')), 900_00));
        $this->overrides->save(new SellingRate($this->tenant, self::PROFILE, RateScope::PROJECT, self::PROJECT, EffectivePeriod::since($this->d('2026-07-01')), 950_00));

        self::assertSame(900_00, $this->resolver->resolve($this->tenant, self::PROFILE, null, self::PROJECT, $this->d('2026-06-15'))->sellingPriceCents);
        self::assertSame(950_00, $this->resolver->resolve($this->tenant, self::PROFILE, null, self::PROJECT, $this->d('2026-09-15'))->sellingPriceCents);
    }

    public function testThrowsWhenNoRateAtAll(): void
    {
        $this->expectException(NoEffectiveRateException::class);
        $this->resolver->resolve($this->tenant, '018f9c4e-0000-7000-8000-0000000fffff', null, null, $this->d('2027-02-01'));
    }

    private function d(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value.' 00:00:00', new DateTimeZone('UTC'));
    }
}
