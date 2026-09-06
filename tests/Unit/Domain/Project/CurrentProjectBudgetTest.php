<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Project;

use App\Domain\Project\BudgetAmendment;
use App\Domain\Project\CurrentProjectBudget;
use App\Domain\Tenant\TenantId;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

/**
 * US-033 (EF-PRJ-8) — budget courant = budget initial + Σ avenants.
 */
final class CurrentProjectBudgetTest extends TestCase
{
    private const string PROJECT = '018f9c4e-0000-7000-8000-00000000aaaa';

    private CurrentProjectBudget $calculator;
    private TenantId $tenant;

    protected function setUp(): void
    {
        $this->calculator = new CurrentProjectBudget();
        $this->tenant = TenantId::generate();
    }

    public function testInitialWithoutAmendmentsIsUnchanged(): void
    {
        $current = $this->calculator->current(100_000_00, 120_000_00, []);

        self::assertSame(100_000_00, $current->costCents);
        self::assertSame(120_000_00, $current->revenueCents);
    }

    public function testAmendmentsAreSummedOntoInitial(): void
    {
        $current = $this->calculator->current(100_000_00, 120_000_00, [
            $this->amendment(20_000_00, 24_000_00),   // extension
            $this->amendment(-5_000_00, 0),           // réduction de charge
        ]);

        self::assertSame(115_000_00, $current->costCents);   // 100 000 + 20 000 − 5 000
        self::assertSame(144_000_00, $current->revenueCents); // 120 000 + 24 000
    }

    public function testNullRevenueBudgetStaysNullWithoutRevenueAmendment(): void
    {
        $current = $this->calculator->current(100_000_00, null, [$this->amendment(10_000_00, 0)]);

        self::assertSame(110_000_00, $current->costCents);
        self::assertNull($current->revenueCents);
    }

    private function amendment(int $deltaCost, int $deltaRevenue): BudgetAmendment
    {
        return BudgetAmendment::record($this->tenant, self::PROJECT, $deltaCost, $deltaRevenue, 'avenant', '018f9c4e-0000-7000-8000-0000000000c1', new DateTimeImmutable('2027-01-10', new DateTimeZone('UTC')));
    }
}
