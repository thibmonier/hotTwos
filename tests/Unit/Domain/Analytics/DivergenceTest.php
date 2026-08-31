<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Analytics;

use App\Domain\Analytics\Divergence;
use PHPUnit\Framework\TestCase;

/**
 * US-005 — l'écart expose son delta absolu et relatif pour le rapport et l'alerte.
 */
final class DivergenceTest extends TestCase
{
    public function testComputesAbsoluteAndRelativeDelta(): void
    {
        $divergence = new Divergence('project_revenue', '2026-08', 100000, 95000);

        self::assertSame(-5000, $divergence->deltaCents());
        self::assertEqualsWithDelta(0.05, $divergence->relativeDelta(), 1e-9);
    }

    public function testRelativeDeltaIsZeroWhenBothNull(): void
    {
        $divergence = new Divergence('project_revenue', '2026-08', 0, 0);

        self::assertSame(0.0, $divergence->relativeDelta());
    }

    public function testRelativeDeltaIsTotalWhenExpectedNullButActualNot(): void
    {
        $divergence = new Divergence('project_revenue', '2026-08', 0, 4200);

        self::assertSame(1.0, $divergence->relativeDelta());
    }
}
