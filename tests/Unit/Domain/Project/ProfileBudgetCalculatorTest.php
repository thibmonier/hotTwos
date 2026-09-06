<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Project;

use App\Domain\Pricing\ProfileRate;
use App\Domain\Pricing\RateResolver;
use App\Domain\Project\LotProfileBudget;
use App\Domain\Project\ProfileBudgetCalculator;
use App\Domain\Shared\EffectivePeriod;
use App\Domain\Tenant\TenantId;
use App\Tests\Support\Pricing\InMemoryProfileRateRepository;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

/**
 * US-078 (EF-PRJ-9, CA-1/CA-3/CA-4) — valorisation du budget de charge par profil aux taux historisés.
 */
final class ProfileBudgetCalculatorTest extends TestCase
{
    private const string LOT = '018f9c4e-0000-7000-8000-00000000aaaa';
    private const string SENIOR = '018f9c4e-0000-7000-8000-0000000000c1';
    private const string JUNIOR = '018f9c4e-0000-7000-8000-0000000000c2';

    private TenantId $tenant;
    private InMemoryProfileRateRepository $rates;
    private ProfileBudgetCalculator $calculator;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $this->rates = new InMemoryProfileRateRepository();
        $this->calculator = new ProfileBudgetCalculator(new RateResolver($this->rates));
    }

    public function testConvertsDaysToSellingAndCostPerProfile(): void
    {
        // senior : vente 800 €/j, coût 500 €/j ; junior : vente 500 €/j, coût 300 €/j.
        $this->rates->save(new ProfileRate($this->tenant, self::SENIOR, EffectivePeriod::since($this->d('2026-01-01')), 500_00, 800_00));
        $this->rates->save(new ProfileRate($this->tenant, self::JUNIOR, EffectivePeriod::since($this->d('2026-01-01')), 300_00, 500_00));

        $breakdown = $this->calculator->compute($this->tenant, [
            new LotProfileBudget($this->tenant, self::LOT, self::SENIOR, 40),
            new LotProfileBudget($this->tenant, self::LOT, self::JUNIOR, 20),
        ], $this->d('2027-02-01'));

        self::assertSame(60, $breakdown->days);
        self::assertSame(42_000_00, $breakdown->sellingCents); // 40×800 + 20×500
        self::assertSame(26_000_00, $breakdown->costCents);    // 40×500 + 20×300
        self::assertFalse($breakdown->hasMissingRate());
    }

    public function testUsesRateEffectiveAtReferenceDate(): void
    {
        // Taux historisé : 800 €/j jusqu'au 01/06, puis 900 €/j.
        $this->rates->save(new ProfileRate($this->tenant, self::SENIOR, EffectivePeriod::between($this->d('2026-01-01'), $this->d('2026-06-01')), 500_00, 800_00));
        $this->rates->save(new ProfileRate($this->tenant, self::SENIOR, EffectivePeriod::since($this->d('2026-06-01')), 550_00, 900_00));

        $before = $this->calculator->compute($this->tenant, [new LotProfileBudget($this->tenant, self::LOT, self::SENIOR, 10)], $this->d('2026-03-01'));
        self::assertSame(8_000_00, $before->sellingCents); // 10×800 (taux d'avant le 01/06)

        $after = $this->calculator->compute($this->tenant, [new LotProfileBudget($this->tenant, self::LOT, self::SENIOR, 10)], $this->d('2026-09-01'));
        self::assertSame(9_000_00, $after->sellingCents); // 10×900
    }

    public function testProfileWithoutRateIsFlaggedNotZeroed(): void
    {
        $this->rates->save(new ProfileRate($this->tenant, self::SENIOR, EffectivePeriod::since($this->d('2026-01-01')), 500_00, 800_00));

        $breakdown = $this->calculator->compute($this->tenant, [
            new LotProfileBudget($this->tenant, self::LOT, self::SENIOR, 10),
            new LotProfileBudget($this->tenant, self::LOT, self::JUNIOR, 5), // aucun taux défini
        ], $this->d('2027-02-01'));

        self::assertTrue($breakdown->hasMissingRate());
        self::assertSame([self::JUNIOR], $breakdown->missingProfileIds);
        self::assertSame(8_000_00, $breakdown->sellingCents); // seul le senior est compté
    }

    private function d(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value.' 00:00:00', new DateTimeZone('UTC'));
    }
}
