<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Finance;

use App\Application\Authorization\Authorizer;
use App\Application\Finance\ConsolidatedFinanceReport;
use App\Domain\Authorization\AccessDeniedException;
use App\Domain\Authorization\DataScope;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\Role;
use App\Domain\Budget\BudgetTrackingCalculator;
use App\Domain\Margin\MarginCalculator;
use App\Domain\Margin\ProjectMargin;
use App\Domain\Project\ContractType;
use App\Domain\Project\Project;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Infrastructure\Budget\DefaultMarginDriftThresholdProvider;
use App\Tests\Support\Authorization\InMemoryRoleRepository;
use App\Tests\Support\Authorization\RecordingSecurityAuditLogger;
use App\Tests\Support\Margin\InMemoryProjectMarginRepository;
use App\Tests\Support\Timesheet\InMemoryProjectRepository;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

/**
 * US-073 (T-073-01/02, CA-1/CA-3) — consolidation multi-projets/clients : totaux tenant, ventilations
 * client/projet, comptage des projets en dérive, filtre client, et gating HAB-1.
 */
final class ConsolidatedFinanceReportTest extends TestCase
{
    private const string PERIOD = '2026-11';

    private TenantId $tenant;
    private InMemoryProjectMarginRepository $margins;
    private InMemoryProjectRepository $projects;
    private RecordingSecurityAuditLogger $audit;
    private ConsolidatedFinanceReport $report;
    private User $executive;
    private User $projectChief;
    private User $collaborator;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();

        $roles = new InMemoryRoleRepository();
        $roles->add(new Role($this->tenant, 'Dirigeant', [Permission::VIEW_PROJECT_FINANCIALS, Permission::VIEW_COLLABORATOR_COST], DataScope::TENANT));
        $roles->add(new Role($this->tenant, 'ChefProjet', [Permission::VIEW_PROJECT_FINANCIALS], DataScope::TENANT));
        $roles->add(new Role($this->tenant, 'Collaborateur', [Permission::VIEW_PROJECT], DataScope::OWN));

        $this->projects = new InMemoryProjectRepository();
        $this->margins = new InMemoryProjectMarginRepository();
        $frozenAt = new DateTimeImmutable('2026-12-01 09:00:00', new DateTimeZone('UTC'));

        // Client ACME : projet A (en dérive) + projet C (partiel, non en dérive).
        $a = Project::createBusiness($this->tenant, 'PRJ-A', 'Projet A', 'ACME', 'resp', 40_000_00, ContractType::FORFAIT, null, null, 60_000_00);
        $c = Project::createBusiness($this->tenant, 'PRJ-C', 'Projet C', 'ACME', 'resp', 8_000_00, ContractType::FORFAIT, null, null, 12_000_00);
        // Client Globex : projet B (budget de charge seul, non en dérive).
        $b = Project::createBusiness($this->tenant, 'PRJ-B', 'Projet B', 'Globex', 'resp', 15_000_00, ContractType::REGIE, null, null);
        foreach ([$a, $b, $c] as $project) {
            $this->projects->save($project);
        }

        $this->margins->replaceForPeriod($this->tenant, self::PERIOD, [
            ProjectMargin::freeze($this->tenant, self::PERIOD, $a->id(), 'Projet A', 42_000_00, 33_000_00, 20, 0, $frozenAt),
            ProjectMargin::freeze($this->tenant, self::PERIOD, $b->id(), 'Projet B', 20_000_00, 12_000_00, 10, 0, $frozenAt),
            ProjectMargin::freeze($this->tenant, self::PERIOD, $c->id(), 'Projet C', 10_000_00, 6_000_00, 5, 2, $frozenAt),
        ]);

        $this->audit = new RecordingSecurityAuditLogger();
        $this->report = new ConsolidatedFinanceReport(
            new Authorizer($roles, $this->audit),
            $this->margins,
            $this->projects,
            new MarginCalculator(),
            new BudgetTrackingCalculator(new MarginCalculator()),
            new DefaultMarginDriftThresholdProvider(),
        );

        $this->executive = new User($this->tenant, 'dg@agence.test', 'hash', ['Dirigeant']);
        $this->projectChief = new User($this->tenant, 'chef@agence.test', 'hash', ['ChefProjet']);
        $this->collaborator = new User($this->tenant, 'collab@agence.test', 'hash', ['Collaborateur']);
    }

    public function testExecutiveSeesConsolidatedTotalsAndBreakdowns(): void
    {
        $d = $this->report->forPeriod($this->executive, self::PERIOD);

        self::assertTrue($d->hasData);
        self::assertTrue($d->costVisible);
        self::assertSame(72_000_00, $d->totalRevenueCents);
        self::assertSame(51_000_00, $d->totalCostCents);
        self::assertSame(21_000_00, $d->totalMarginCents);
        self::assertSame(3, $d->projectCount);
        self::assertSame(1, $d->driftingProjectCount); // seul Projet A dérive
        self::assertTrue($d->hasPartial);              // Projet C partiel

        // Ventilation client triée CA décroissant : ACME (52 000) puis Globex (20 000).
        self::assertCount(2, $d->byClient);
        self::assertSame('ACME', $d->byClient[0]->clientName);
        self::assertSame(52_000_00, $d->byClient[0]->revenueCents);
        self::assertSame(13_000_00, $d->byClient[0]->marginCents);
        self::assertSame(2, $d->byClient[0]->projectCount);
        self::assertSame('Globex', $d->byClient[1]->clientName);
    }

    public function testExecutiveReadOfCostIsTraced(): void
    {
        $this->report->forPeriod($this->executive, self::PERIOD);

        self::assertTrue($this->audit->has('sensitive_data_read'));
    }

    public function testProjectChiefSeesRevenueButNotCostMarginOrDrift(): void
    {
        $d = $this->report->forPeriod($this->projectChief, self::PERIOD);

        self::assertFalse($d->costVisible);
        self::assertSame(72_000_00, $d->totalRevenueCents);
        self::assertNull($d->totalCostCents);
        self::assertNull($d->totalMarginCents);
        self::assertNull($d->driftingProjectCount);
        self::assertNull($d->byClient[0]->costCents);
        self::assertNull($d->byProject[0]->marginCents);
    }

    public function testUnauthorizedUserIsDenied(): void
    {
        $this->expectException(AccessDeniedException::class);

        $this->report->forPeriod($this->collaborator, self::PERIOD);
    }

    public function testClientFilterRestrictsScope(): void
    {
        $d = $this->report->forPeriod($this->executive, self::PERIOD, 'ACME');

        self::assertSame('ACME', $d->clientFilter);
        self::assertSame(52_000_00, $d->totalRevenueCents);
        self::assertSame(2, $d->projectCount);
        self::assertCount(1, $d->byClient);
        self::assertSame('ACME', $d->byClient[0]->clientName);
    }

    public function testDefaultsToLatestPeriodAndReportsAvailablePeriods(): void
    {
        $frozenAt = new DateTimeImmutable('2026-11-01 09:00:00', new DateTimeZone('UTC'));
        $this->margins->replaceForPeriod($this->tenant, '2026-10', [
            ProjectMargin::freeze($this->tenant, '2026-10', 'p-old', 'Ancien', 1_000_00, 500_00, 1, 0, $frozenAt),
        ]);

        $d = $this->report->forPeriod($this->executive);

        self::assertSame(self::PERIOD, $d->period); // 2026-11 est la plus récente
        self::assertSame(['2026-11', '2026-10'], $d->availablePeriods);
    }

    public function testNoFrozenDataReportsEmptyDashboard(): void
    {
        $other = new User(TenantId::generate(), 'dg2@agence.test', 'hash', ['Dirigeant']);
        $roles = new InMemoryRoleRepository();
        $roles->add(new Role($other->tenantId(), 'Dirigeant', [Permission::VIEW_PROJECT_FINANCIALS, Permission::VIEW_COLLABORATOR_COST], DataScope::TENANT));
        $report = new ConsolidatedFinanceReport(
            new Authorizer($roles, new RecordingSecurityAuditLogger()),
            new InMemoryProjectMarginRepository(),
            new InMemoryProjectRepository(),
            new MarginCalculator(),
            new BudgetTrackingCalculator(new MarginCalculator()),
            new DefaultMarginDriftThresholdProvider(),
        );

        $d = $report->forPeriod($other);

        self::assertFalse($d->hasData);
        self::assertNull($d->period);
        self::assertSame([], $d->availablePeriods);
        self::assertSame([], $d->byProject);
    }
}
