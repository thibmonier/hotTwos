<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Currency;

use App\Domain\Currency\CurrencyConverter;
use App\Domain\Currency\ExchangeRate;
use App\Domain\Currency\ReferenceCurrency;
use App\Domain\Shared\EffectivePeriod;
use App\Domain\Tenant\TenantId;
use App\Tests\Support\Currency\InMemoryExchangeRateRepository;
use App\Tests\Support\Currency\InMemoryReferenceCurrencyRepository;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

/**
 * US-016 (EF-REF-22, CA-1..CA-4) — conversion vers la devise de référence au taux daté, défaut EUR,
 * taux manquant signalé.
 */
final class CurrencyConverterTest extends TestCase
{
    private TenantId $tenant;
    private InMemoryReferenceCurrencyRepository $references;
    private InMemoryExchangeRateRepository $rates;
    private CurrencyConverter $converter;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $this->references = new InMemoryReferenceCurrencyRepository();
        $this->rates = new InMemoryExchangeRateRepository();
        $this->converter = new CurrencyConverter($this->references, $this->rates);
    }

    public function testReferenceDefaultsToEurIdentity(): void
    {
        // Sans configuration : référence EUR, un montant EUR est renvoyé tel quel.
        $result = $this->converter->toReference($this->tenant, 1_000_00, 'EUR', $this->d('2027-02-01'));

        self::assertTrue($result->available);
        self::assertSame(1_000_00, $result->referenceCents);
    }

    public function testConvertsAtEffectiveRate(): void
    {
        // Référence EUR ; CHF→EUR = 1,05 (1050 millièmes).
        $this->rates->save(new ExchangeRate($this->tenant, 'CHF', EffectivePeriod::since($this->d('2026-01-01')), 1050));

        $result = $this->converter->toReference($this->tenant, 1_000_00, 'CHF', $this->d('2027-02-01'));

        self::assertTrue($result->available);
        self::assertSame(1_050_00, $result->referenceCents); // 1000 CHF → 1050 EUR
    }

    public function testUsesRateEffectiveAtDate(): void
    {
        $this->rates->save(new ExchangeRate($this->tenant, 'CHF', EffectivePeriod::between($this->d('2026-01-01'), $this->d('2026-07-01')), 1050));
        $this->rates->save(new ExchangeRate($this->tenant, 'CHF', EffectivePeriod::since($this->d('2026-07-01')), 1080));

        self::assertSame(1_050_00, $this->converter->toReference($this->tenant, 1_000_00, 'CHF', $this->d('2026-06-15'))->referenceCents);
        self::assertSame(1_080_00, $this->converter->toReference($this->tenant, 1_000_00, 'CHF', $this->d('2026-09-15'))->referenceCents);
    }

    public function testMissingRateIsSignalled(): void
    {
        $result = $this->converter->toReference($this->tenant, 1_000_00, 'USD', $this->d('2027-02-01'));

        self::assertFalse($result->available);
        self::assertNull($result->referenceCents);
    }

    public function testHonoursConfiguredReferenceCurrency(): void
    {
        $this->references->save(new ReferenceCurrency($this->tenant, 'CHF'));
        // Référence = CHF ; un montant CHF est l'identité, un montant EUR nécessite un taux EUR→CHF.
        self::assertSame(500_00, $this->converter->toReference($this->tenant, 500_00, 'CHF', $this->d('2027-02-01'))->referenceCents);
        self::assertFalse($this->converter->toReference($this->tenant, 500_00, 'EUR', $this->d('2027-02-01'))->available);
    }

    private function d(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value.' 00:00:00', new DateTimeZone('UTC'));
    }
}
