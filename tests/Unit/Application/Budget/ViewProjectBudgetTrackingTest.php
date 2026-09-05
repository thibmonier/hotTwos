<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Budget;

use App\Application\Authorization\Authorizer;
use App\Application\Budget\ViewProjectBudgetTracking;
use App\Domain\Authorization\AccessDeniedException;
use App\Domain\Authorization\DataScope;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\Role;
use App\Domain\Budget\BudgetTrackingCalculator;
use App\Domain\Margin\MarginCalculator;
use App\Domain\Project\ContractType;
use App\Domain\Project\Project;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Domain\Valuation\ProjectValuationLine;
use App\Infrastructure\Budget\DefaultMarginDriftThresholdProvider;
use App\Tests\Support\Authorization\InMemoryRoleRepository;
use App\Tests\Support\Authorization\RecordingSecurityAuditLogger;
use App\Tests\Support\Timesheet\InMemoryProjectRepository;
use App\Tests\Support\Valuation\InMemoryTimeEntryValuationRepository;
use PHPUnit\Framework\TestCase;

/**
 * US-072 (T-072-01/03, CA-1/CA-4, HAB-1) — lecture gated du suivi budgétaire : coût/marge/dérive
 * réservés à VIEW_COLLABORATOR_COST (CA visible sinon), accès gated VIEW_PROJECT_FINANCIALS,
 * lecture sensible tracée, projet sans budget.
 */
final class ViewProjectBudgetTrackingTest extends TestCase
{
    private TenantId $tenant;
    private InMemoryProjectRepository $projects;
    private InMemoryTimeEntryValuationRepository $valuations;
    private RecordingSecurityAuditLogger $audit;
    private ViewProjectBudgetTracking $view;
    private User $finance;
    private User $projectChief;
    private User $collaborator;
    private Project $project;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();

        $roles = new InMemoryRoleRepository();
        $roles->add(new Role($this->tenant, 'Finance', [Permission::VIEW_PROJECT_FINANCIALS, Permission::VIEW_COLLABORATOR_COST], DataScope::TENANT));
        $roles->add(new Role($this->tenant, 'ChefProjet', [Permission::VIEW_PROJECT_FINANCIALS], DataScope::TENANT));
        $roles->add(new Role($this->tenant, 'Collaborateur', [Permission::VIEW_PROJECT], DataScope::OWN));

        $this->projects = new InMemoryProjectRepository();
        $this->project = Project::createBusiness(
            $this->tenant,
            'PRJ-0001',
            'Refonte app',
            'ACME',
            'resp-1',
            40_000_00,
            ContractType::FORFAIT,
            null,
            null,
            60_000_00,
        );
        $this->projects->save($this->project);

        $this->valuations = new InMemoryTimeEntryValuationRepository();
        $this->valuations->projectBreakdown = [
            new ProjectValuationLine($this->project->id(), 'Refonte app', 20, 42_000_00, 30_000_00),
        ];

        $this->audit = new RecordingSecurityAuditLogger();
        $this->view = new ViewProjectBudgetTracking(
            new Authorizer($roles, $this->audit),
            $this->projects,
            $this->valuations,
            new BudgetTrackingCalculator(new MarginCalculator()),
            new DefaultMarginDriftThresholdProvider(),
        );

        $this->finance = new User($this->tenant, 'finance@agence.test', 'hash', ['Finance']);
        $this->projectChief = new User($this->tenant, 'chef@agence.test', 'hash', ['ChefProjet']);
        $this->collaborator = new User($this->tenant, 'collab@agence.test', 'hash', ['Collaborateur']);
    }

    public function testFinanceSeesBudgetCostMarginAndConsumption(): void
    {
        $view = $this->view->forProject($this->finance, $this->project->id());

        self::assertTrue($view->hasBudget);
        self::assertTrue($view->costVisible);
        self::assertSame(60_000_00, $view->revenueBudgetCents);
        self::assertSame(42_000_00, $view->realizedRevenueCents);
        self::assertSame(40_000_00, $view->costBudgetCents);
        self::assertSame(30_000_00, $view->realizedCostCents);
        self::assertSame(75.0, $view->consumptionPercent);
        self::assertSame(12_000_00, $view->realizedMarginCents);
        self::assertSame(28.57, $view->realizedMarginRatePercent);
        self::assertFalse($view->isDrifting); // dérive 4,76 < 5
    }

    public function testFinanceReadOfCostIsTraced(): void
    {
        $this->view->forProject($this->finance, $this->project->id());

        self::assertTrue($this->audit->has('sensitive_data_read'));
    }

    public function testProjectChiefSeesRevenueButNotCostOrMargin(): void
    {
        $view = $this->view->forProject($this->projectChief, $this->project->id());

        self::assertFalse($view->costVisible);
        self::assertSame(60_000_00, $view->revenueBudgetCents);
        self::assertSame(42_000_00, $view->realizedRevenueCents);
        self::assertSame(-18_000_00, $view->revenueVarianceCents);
        self::assertNull($view->costBudgetCents);
        self::assertNull($view->realizedCostCents);
        self::assertNull($view->consumptionPercent);
        self::assertNull($view->realizedMarginCents);
        self::assertNull($view->marginRateDriftPoints);
        self::assertFalse($view->isDrifting);
    }

    public function testProjectChiefReadIsNotTracedAsSensitive(): void
    {
        $this->view->forProject($this->projectChief, $this->project->id());

        self::assertFalse($this->audit->has('sensitive_data_read'));
    }

    public function testUnauthorizedUserIsDenied(): void
    {
        $this->expectException(AccessDeniedException::class);

        $this->view->forProject($this->collaborator, $this->project->id());
    }

    public function testProjectWithoutBudgetDisablesComparison(): void
    {
        $noBudget = new Project($this->tenant, 'PRJ-0002', 'Projet interne');
        $this->projects->save($noBudget);
        $this->valuations->projectBreakdown = [
            new ProjectValuationLine($noBudget->id(), 'Projet interne', 5, 0, 4_000_00),
        ];

        $view = $this->view->forProject($this->finance, $noBudget->id());

        self::assertFalse($view->hasBudget);
        self::assertNull($view->costBudgetCents);
        self::assertNull($view->revenueBudgetCents);
        // Réalisé affiché malgré l'absence de budget (CA-4).
        self::assertSame(4_000_00, $view->realizedCostCents);
    }
}
