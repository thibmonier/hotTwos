<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Budget;

use App\Application\Budget\CaptureChargeLandingSnapshots;
use App\Domain\Budget\ChargeLandingCalculator;
use App\Domain\Project\ContractType;
use App\Domain\Project\CurrentProjectBudget;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectLot;
use App\Domain\Project\ProjectProgressCalculator;
use App\Domain\Tenant\TenantId;
use App\Domain\Valuation\ProjectValuationLine;
use App\Infrastructure\Budget\TenantChargeDriftThresholdProvider;
use App\Tests\Support\Budget\InMemoryChargeDriftThresholdRepository;
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
 * US-079c (EF-PRJ-16) — capture de l'atterrissage à la clôture : idempotence par (tenant, projet,
 * période), réutilisation de la règle OBJ-2 (dérive précoce), aucune capture si atterrissage indisponible.
 */
final class CaptureChargeLandingSnapshotsTest extends TestCase
{
    private const string RESPONSIBLE = '018f9c4e-0000-7000-8000-0000000000c1';

    private TenantId $tenant;
    private InMemoryProjectRepository $projects;
    private InMemoryTimeEntryValuationRepository $valuations;
    private InMemoryProjectLotRepository $lots;
    private InMemoryChargeLandingSnapshotRepository $snapshots;
    private CaptureChargeLandingSnapshots $capture;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $this->projects = new InMemoryProjectRepository();
        $this->valuations = new InMemoryTimeEntryValuationRepository();
        $this->lots = new InMemoryProjectLotRepository();
        $this->snapshots = new InMemoryChargeLandingSnapshotRepository();

        $this->capture = new CaptureChargeLandingSnapshots(
            $this->projects,
            $this->valuations,
            new InMemoryBudgetAmendmentRepository(),
            new CurrentProjectBudget(),
            $this->lots,
            new ProjectProgressCalculator(),
            new ChargeLandingCalculator(),
            new TenantChargeDriftThresholdProvider(new InMemoryChargeDriftThresholdRepository()),
            $this->snapshots,
            new MockClock(new DateTimeImmutable('2026-08-31 23:00:00', new DateTimeZone('UTC'))),
        );
    }

    public function testCapturesEarlyDriftLandingForBudgetedProject(): void
    {
        // Budget charge 100 000 € ; consommé 15 000 € à 10 % d'avancement → atterrissage 150 000 €
        // (+50 %), consommation 15 % (< 50 %) → dérive de charge précoce (OBJ-2).
        $project = $this->budgetedProject('PRJ-0001', 'Pilotage');
        $this->seedProgress($project->id(), 10);
        $this->valuations->projectBreakdown = [
            new ProjectValuationLine($project->id(), 'Pilotage', 3, 6_000_00, 15_000_00),
        ];

        $this->capture->forClosedPeriod($this->tenant, '2026-08');

        self::assertCount(1, $this->snapshots->snapshots);
        $snapshot = $this->snapshots->findForProject($this->tenant, $project->id())[0];
        self::assertSame('2026-08', $snapshot->period());
        self::assertSame(150_000_00, $snapshot->landingCostCents());
        self::assertSame(50.0, $snapshot->overrunPercent());
        self::assertTrue($snapshot->isEarlyDrift());
    }

    public function testIsIdempotentPerProjectAndPeriod(): void
    {
        $project = $this->budgetedProject('PRJ-0001', 'Pilotage');
        $this->seedProgress($project->id(), 10);
        $this->valuations->projectBreakdown = [
            new ProjectValuationLine($project->id(), 'Pilotage', 3, 6_000_00, 15_000_00),
        ];

        $this->capture->forClosedPeriod($this->tenant, '2026-08');
        $this->capture->forClosedPeriod($this->tenant, '2026-08');

        // Re-clôture de la même période : remplace, n'ajoute pas.
        self::assertCount(1, $this->snapshots->snapshots);
    }

    public function testBuildsSeriesAcrossPeriods(): void
    {
        $project = $this->budgetedProject('PRJ-0001', 'Pilotage');
        $this->seedProgress($project->id(), 10);
        $this->valuations->projectBreakdown = [
            new ProjectValuationLine($project->id(), 'Pilotage', 3, 6_000_00, 15_000_00),
        ];

        $this->capture->forClosedPeriod($this->tenant, '2026-07');
        $this->capture->forClosedPeriod($this->tenant, '2026-08');

        $series = $this->snapshots->findForProject($this->tenant, $project->id());
        self::assertCount(2, $series);
        self::assertSame(['2026-07', '2026-08'], [$series[0]->period(), $series[1]->period()]);
    }

    public function testSkipsProjectWithoutAvailableLanding(): void
    {
        // Projet sans budget de charge ni avancement → atterrissage indisponible, aucun snapshot.
        $project = new Project($this->tenant, 'PRJ-0002', 'Projet interne');
        $this->projects->save($project);

        $this->capture->forClosedPeriod($this->tenant, '2026-08');

        self::assertCount(0, $this->snapshots->snapshots);
    }

    private function budgetedProject(string $code, string $name): Project
    {
        $project = Project::createBusiness(
            $this->tenant,
            $code,
            $name,
            'ACME',
            self::RESPONSIBLE,
            100_000_00,
            ContractType::FORFAIT,
            null,
            null,
            120_000_00,
        );
        $this->projects->save($project);

        return $project;
    }

    private function seedProgress(string $projectId, int $percent): void
    {
        $lot = new ProjectLot($this->tenant, $projectId, 'Développement', 10, 8_000_000);
        $lot->recordProgress($percent, null);
        $this->lots->lots[] = $lot;
    }
}
