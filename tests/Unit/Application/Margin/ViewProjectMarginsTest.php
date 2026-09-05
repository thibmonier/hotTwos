<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Margin;

use App\Application\Authorization\Authorizer;
use App\Application\Margin\ViewProjectMargins;
use App\Domain\Authorization\AccessDeniedException;
use App\Domain\Authorization\DataScope;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\Role;
use App\Domain\Margin\MarginCalculator;
use App\Domain\Margin\ProjectMargin;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Tests\Support\Authorization\InMemoryRoleRepository;
use App\Tests\Support\Authorization\RecordingSecurityAuditLogger;
use App\Tests\Support\Margin\InMemoryProjectMarginRepository;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

/**
 * US-071 (T-071-06, CA-5) — lecture gated des marges : le coût et la marge sont réservés à
 * l'habilitation coût (HAB-1) et tracés (HAB-6) ; l'accès exige VIEW_PROJECT_FINANCIALS.
 */
final class ViewProjectMarginsTest extends TestCase
{
    private const string PROJECT_A = '018f9c4e-0000-7000-8000-00000000aaaa';
    private const string PROJECT_B = '018f9c4e-0000-7000-8000-00000000bbbb';

    private TenantId $tenant;
    private InMemoryProjectMarginRepository $margins;
    private RecordingSecurityAuditLogger $audit;
    private ViewProjectMargins $view;
    private User $finance;
    private User $projectChief;
    private User $collaborator;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();

        $roles = new InMemoryRoleRepository();
        $roles->add(new Role($this->tenant, 'Finance', [Permission::VIEW_PROJECT_FINANCIALS, Permission::VIEW_COLLABORATOR_COST], DataScope::TENANT));
        $roles->add(new Role($this->tenant, 'ChefProjet', [Permission::VIEW_PROJECT_FINANCIALS], DataScope::TENANT));
        $roles->add(new Role($this->tenant, 'Collaborateur', [Permission::VIEW_PROJECT], DataScope::OWN));

        $this->margins = new InMemoryProjectMarginRepository();
        $this->audit = new RecordingSecurityAuditLogger();
        $this->view = new ViewProjectMargins(
            new Authorizer($roles, $this->audit),
            $this->margins,
            new MarginCalculator(),
        );

        $this->finance = new User($this->tenant, 'finance@agence.test', 'hash', ['Finance']);
        $this->projectChief = new User($this->tenant, 'chef@agence.test', 'hash', ['ChefProjet']);
        $this->collaborator = new User($this->tenant, 'collab@agence.test', 'hash', ['Collaborateur']);

        $this->seedMargins();
    }

    public function testFinanceSeesCostMarginAndRate(): void
    {
        $report = $this->view->forPeriod($this->finance, '2026-11');

        self::assertTrue($report->costVisible);
        self::assertCount(2, $report->rows);

        $a = $report->rows[0]; // trié CA décroissant : Site vitrine (10 000) en tête
        self::assertSame(self::PROJECT_A, $a->projectRef);
        self::assertSame(10_000_00, $a->revenueCents);
        self::assertSame(5_800_00, $a->costCents);
        self::assertSame(4_200_00, $a->marginCents);
        self::assertSame(42.0, $a->marginRatePercent);

        self::assertSame(16_000_00, $report->totalRevenueCents);
        self::assertSame(3_700_00, $report->totalMarginCents);
    }

    public function testFinanceReadOfCostIsTraced(): void
    {
        $this->view->forPeriod($this->finance, '2026-11');

        self::assertTrue($this->audit->has('sensitive_data_read'));
    }

    public function testProjectChiefSeesRevenueButNotCostOrMargin(): void
    {
        $report = $this->view->forPeriod($this->projectChief, '2026-11');

        self::assertFalse($report->costVisible);
        self::assertNull($report->totalMarginCents);
        self::assertSame(16_000_00, $report->totalRevenueCents);

        $a = $report->rows[0];
        self::assertSame(10_000_00, $a->revenueCents);
        self::assertNull($a->costCents);
        self::assertNull($a->marginCents);
        self::assertNull($a->marginRatePercent);
    }

    public function testProjectChiefReadIsNotTracedAsSensitive(): void
    {
        $this->view->forPeriod($this->projectChief, '2026-11');

        self::assertFalse($this->audit->has('sensitive_data_read'));
    }

    public function testUnauthorizedUserIsDenied(): void
    {
        $this->expectException(AccessDeniedException::class);

        $this->view->forPeriod($this->collaborator, '2026-11');
    }

    public function testPartialMarginIsFlaggedGlobally(): void
    {
        $report = $this->view->forPeriod($this->finance, '2026-11');

        self::assertTrue($report->hasPartial);
        $b = $report->rows[1]; // Appli mobile, valorisation incomplète
        self::assertTrue($b->partial);
        self::assertSame(2, $b->unvaluedCount);
    }

    private function seedMargins(): void
    {
        $frozenAt = new DateTimeImmutable('2026-12-01 09:00:00', new DateTimeZone('UTC'));

        $this->margins->replaceForPeriod($this->tenant, '2026-11', [
            ProjectMargin::freeze($this->tenant, '2026-11', self::PROJECT_A, 'Site vitrine', 10_000_00, 5_800_00, 12, 0, $frozenAt),
            ProjectMargin::freeze($this->tenant, '2026-11', self::PROJECT_B, 'Appli mobile', 6_000_00, 6_500_00, 8, 2, $frozenAt),
        ]);
    }
}
