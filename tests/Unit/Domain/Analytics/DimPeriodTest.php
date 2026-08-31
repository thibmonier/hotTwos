<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Analytics;

use App\Domain\Analytics\DimPeriod;
use App\Domain\Tenant\TenantId;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

/**
 * US-005 — la dimension période dérive de la clé YYYY-MM et rejette une valeur invalide.
 */
final class DimPeriodTest extends TestCase
{
    public function testKeepsPeriodKey(): void
    {
        $dim = new DimPeriod(TenantId::generate(), '2026-08');

        self::assertSame('2026-08', $dim->period());
    }

    public function testRejectsMalformedPeriod(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DimPeriod(TenantId::generate(), '2026-8');
    }
}
