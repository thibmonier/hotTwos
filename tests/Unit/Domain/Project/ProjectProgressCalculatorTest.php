<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Project;

use App\Domain\Project\ProjectLot;
use App\Domain\Project\ProjectProgressCalculator;
use App\Domain\Tenant\TenantId;
use PHPUnit\Framework\TestCase;

/**
 * US-036 (T-036-02) — avancement physique projet = moyenne pondérée par la charge (`budgetDays`) des
 * lots qui déclarent un avancement.
 */
final class ProjectProgressCalculatorTest extends TestCase
{
    private const string PROJECT = '018f9c4e-0000-7000-8000-00000000aaaa';

    private ProjectProgressCalculator $calculator;
    private TenantId $tenant;

    protected function setUp(): void
    {
        $this->calculator = new ProjectProgressCalculator();
        $this->tenant = TenantId::generate();
    }

    public function testWeightedByBudgetDays(): void
    {
        // Lot 30j à 20 % + lot 10j à 60 % → (30*20 + 10*60) / 40 = 1200/40 = 30 %.
        $a = $this->lot(30, 20);
        $b = $this->lot(10, 60);

        self::assertSame(30, $this->calculator->weightedPhysicalProgress([$a, $b]));
    }

    public function testLotsWithoutProgressAreIgnored(): void
    {
        $a = $this->lot(30, 40);
        $b = $this->lot(10, null); // pas d'avancement déclaré → ignoré

        self::assertSame(40, $this->calculator->weightedPhysicalProgress([$a, $b]));
    }

    public function testReturnsNullWhenNoProgressDeclared(): void
    {
        self::assertNull($this->calculator->weightedPhysicalProgress([$this->lot(30, null)]));
        self::assertNull($this->calculator->weightedPhysicalProgress([]));
    }

    private function lot(int $budgetDays, ?int $progress): ProjectLot
    {
        $lot = new ProjectLot($this->tenant, self::PROJECT, 'Lot', $budgetDays, 1_000_000);
        if (null !== $progress) {
            $lot->recordProgress($progress, null);
        }

        return $lot;
    }
}
