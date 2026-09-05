<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Margin;

use App\Application\Margin\ComputeProjectMargins;
use App\Application\Margin\FreezeProjectMarginsOnPeriodClosed;
use App\Application\Period\Message\PeriodClosed;
use App\Domain\Tenant\TenantId;
use App\Domain\Valuation\ProjectValuationLine;
use App\Tests\Support\Margin\InMemoryProjectMarginRepository;
use App\Tests\Support\Valuation\InMemoryTimeEntryValuationRepository;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

/**
 * US-071 (T-071-05, CA-1) — branchement clôture : à la consommation de {@see PeriodClosed}, la marge
 * des projets du mois est figée pour le tenant porté par le message (parité worker).
 */
final class FreezeProjectMarginsOnPeriodClosedTest extends TestCase
{
    private const string PROJECT = '018f9c4e-0000-7000-8000-00000000aaaa';

    public function testFreezesMarginsForTheClosedPeriodTenant(): void
    {
        $tenant = TenantId::generate();
        $valuations = new InMemoryTimeEntryValuationRepository();
        $valuations->projectBreakdownForPeriod = [
            new ProjectValuationLine(self::PROJECT, 'Site vitrine', 12, 10_000_00, 5_800_00),
        ];
        $margins = new InMemoryProjectMarginRepository();
        $handler = new FreezeProjectMarginsOnPeriodClosed(new ComputeProjectMargins(
            $valuations,
            $margins,
            new MockClock(new DateTimeImmutable('2026-12-01 09:00:00', new DateTimeZone('UTC'))),
        ));

        $handler(new PeriodClosed($tenant->toString(), '2026-11'));

        $frozen = $margins->findForPeriod($tenant, '2026-11');
        self::assertCount(1, $frozen);
        self::assertSame(4_200_00, $frozen[0]->marginCents());
    }
}
