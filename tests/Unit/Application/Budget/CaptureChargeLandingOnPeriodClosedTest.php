<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Budget;

use App\Application\Budget\CaptureChargeLandingOnPeriodClosed;
use App\Application\Budget\CaptureChargeLandingSnapshots;
use App\Application\Period\Message\PeriodClosed;
use App\Domain\Budget\ChargeLandingCalculator;
use App\Domain\Project\ContractType;
use App\Domain\Project\CurrentProjectBudget;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectLot;
use App\Domain\Project\ProjectProgressCalculator;
use App\Domain\Tenant\TenantId;
use App\Domain\Valuation\ProjectValuationLine;
use App\Tests\Support\Budget\InMemoryChargeLandingSnapshotRepository;
use App\Tests\Support\Project\InMemoryBudgetAmendmentRepository;
use App\Tests\Support\Project\InMemoryProjectLotRepository;
use App\Tests\Support\Timesheet\InMemoryProjectRepository;
use App\Tests\Support\Valuation\InMemoryTimeEntryValuationRepository;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

/**
 * US-079c (EF-PRJ-16) — branchement clôture : à la consommation de {@see PeriodClosed}, l'atterrissage
 * en charge des projets du tenant est capturé (handler séparé du figeage de marge).
 */
final class CaptureChargeLandingOnPeriodClosedTest extends TestCase
{
    private const string RESPONSIBLE = '018f9c4e-0000-7000-8000-0000000000c1';

    public function testCapturesLandingForTheClosedPeriodTenant(): void
    {
        $tenant = TenantId::generate();
        $projects = new InMemoryProjectRepository();
        $project = Project::createBusiness($tenant, 'PRJ-0001', 'Pilotage', 'ACME', self::RESPONSIBLE, 100_000_00, ContractType::FORFAIT, null, null, 120_000_00);
        $projects->save($project);

        $lots = new InMemoryProjectLotRepository();
        $lot = new ProjectLot($tenant, $project->id(), 'Développement', 10, 8_000_000);
        $lot->recordProgress(10, null);
        $lots->lots[] = $lot;

        $valuations = new InMemoryTimeEntryValuationRepository();
        $valuations->projectBreakdown = [new ProjectValuationLine($project->id(), 'Pilotage', 3, 6_000_00, 15_000_00)];

        $snapshots = new InMemoryChargeLandingSnapshotRepository();
        $handler = new CaptureChargeLandingOnPeriodClosed(new CaptureChargeLandingSnapshots(
            $projects,
            $valuations,
            new InMemoryBudgetAmendmentRepository(),
            new CurrentProjectBudget(),
            $lots,
            new ProjectProgressCalculator(),
            new ChargeLandingCalculator(),
            $snapshots,
            new MockClock(new DateTimeImmutable('2026-08-31 23:00:00', new DateTimeZone('UTC'))),
        ));

        $handler(new PeriodClosed($tenant->toString(), '2026-08'));

        $series = $snapshots->findForProject($tenant, $project->id());
        self::assertCount(1, $series);
        self::assertSame('2026-08', $series[0]->period());
        self::assertTrue($series[0]->isEarlyDrift());
    }
}
