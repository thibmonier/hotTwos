<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Invoice;

use App\Application\Authorization\Authorizer;
use App\Application\Invoice\IssueInvoice;
use App\Application\Margin\ComputeProjectMargins;
use App\Domain\Authorization\AccessDeniedException;
use App\Domain\Authorization\DataScope;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\Role;
use App\Domain\Invoice\InvoiceException;
use App\Domain\Invoice\InvoiceStatus;
use App\Domain\Project\ContractType;
use App\Domain\Project\Project;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Domain\Valuation\PeriodClosureStatus;
use App\Domain\Valuation\ProjectValuationLine;
use App\Infrastructure\Margin\InvoiceRevenueSource;
use App\Tests\Support\Authorization\InMemoryRoleRepository;
use App\Tests\Support\Authorization\RecordingSecurityAuditLogger;
use App\Tests\Support\Invoice\InMemoryInvoiceRepository;
use App\Tests\Support\Margin\InMemoryProjectMarginRepository;
use App\Tests\Support\Timesheet\InMemoryProjectRepository;
use App\Tests\Support\Valuation\InMemoryTimeEntryValuationRepository;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

/**
 * US-075 (T-075-03/06) — émission manuelle : période clôturée + montant > 0 + gating (HAB-1) + trace
 * (HAB-6) ; client copié du projet ; total facturé par période.
 */
final class IssueInvoiceTest extends TestCase
{
    private const string PERIOD = '2026-11';
    private const string CLIENT = '018f9c4e-0000-7000-8000-0000000000c9';

    private TenantId $tenant;
    private InMemoryProjectRepository $projects;
    private InMemoryInvoiceRepository $invoices;
    private InMemoryTimeEntryValuationRepository $valuations;
    private InMemoryProjectMarginRepository $margins;
    private RecordingSecurityAuditLogger $audit;
    private InvoiceClosureStub $closure;
    private IssueInvoice $issue;
    private User $finance;
    private User $collaborator;
    private Project $project;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();

        $roles = new InMemoryRoleRepository();
        $roles->add(new Role($this->tenant, 'Finance', [Permission::VIEW_PROJECT_FINANCIALS], DataScope::TENANT));
        $roles->add(new Role($this->tenant, 'Collaborateur', [Permission::VIEW_PROJECT], DataScope::OWN));

        $this->projects = new InMemoryProjectRepository();
        $this->project = Project::createBusiness($this->tenant, 'PRJ-A', 'Refonte app', 'ACME', 'resp-1', 40_000_00, ContractType::FORFAIT, null, null);
        $this->project->attachClient(self::CLIENT);
        $this->projects->save($this->project);

        $this->invoices = new InMemoryInvoiceRepository();
        $this->valuations = new InMemoryTimeEntryValuationRepository();
        $this->margins = new InMemoryProjectMarginRepository();
        $this->closure = new InvoiceClosureStub(true);
        $this->audit = new RecordingSecurityAuditLogger();
        $clock = new MockClock(new DateTimeImmutable('2026-12-05 10:00:00', new DateTimeZone('UTC')));
        $computeMargins = new ComputeProjectMargins($this->valuations, $this->margins, new InvoiceRevenueSource($this->invoices), $clock);
        $this->issue = new IssueInvoice(
            new Authorizer($roles, $this->audit),
            $this->closure,
            $this->projects,
            $this->invoices,
            $computeMargins,
            $this->audit,
            $clock,
        );

        $this->finance = new User($this->tenant, 'finance@agence.test', 'hash', ['Finance']);
        $this->collaborator = new User($this->tenant, 'collab@agence.test', 'hash', ['Collaborateur']);
    }

    public function testFinanceIssuesInvoiceAndReadIsTraced(): void
    {
        $invoice = $this->issue->issue($this->finance, $this->project->id(), self::PERIOD, 42_000_00);

        self::assertSame(42_000_00, $invoice->amountCents());
        self::assertSame(self::PERIOD, $invoice->period());
        self::assertSame(self::CLIENT, $invoice->clientId());          // client copié du projet
        self::assertSame(InvoiceStatus::ISSUED, $invoice->status());
        self::assertCount(1, $this->invoices->invoices);
        self::assertTrue($this->audit->has('invoice_issued'));         // HAB-6
    }

    public function testUnauthorizedUserIsDenied(): void
    {
        $this->expectException(AccessDeniedException::class);

        $this->issue->issue($this->collaborator, $this->project->id(), self::PERIOD, 42_000_00);
    }

    public function testOpenPeriodIsRefused(): void
    {
        $this->closure->closed = false;

        $this->expectException(InvoiceException::class);
        $this->issue->issue($this->finance, $this->project->id(), self::PERIOD, 42_000_00);
    }

    public function testNonPositiveAmountIsRefused(): void
    {
        $this->expectException(InvoiceException::class);

        $this->issue->issue($this->finance, $this->project->id(), self::PERIOD, 0);
    }

    public function testUnknownProjectIsRefused(): void
    {
        $this->expectException(InvoiceException::class);

        $this->issue->issue($this->finance, 'unknown-project', self::PERIOD, 42_000_00);
    }

    public function testTotalForProjectPeriodSumsIssuedInvoices(): void
    {
        $this->issue->issue($this->finance, $this->project->id(), self::PERIOD, 30_000_00);
        $this->issue->issue($this->finance, $this->project->id(), self::PERIOD, 12_000_00);

        self::assertSame(42_000_00, $this->invoices->totalForProjectPeriod($this->tenant, $this->project->id(), self::PERIOD));
    }

    public function testEmissionRefreezesMarginWithBilledRevenue(): void
    {
        // Marge d'abord figée sur le CA reconnu (10 000 − 5 800).
        $this->valuations->projectBreakdownForPeriod = [
            new ProjectValuationLine($this->project->id(), 'Refonte app', 12, 10_000_00, 5_800_00),
        ];

        // Émission d'une facture de 12 000 → re-figeage : la marge retient le facturé réel (US-076).
        $this->issue->issue($this->finance, $this->project->id(), self::PERIOD, 12_000_00);

        $margin = $this->margins->findForPeriod($this->tenant, self::PERIOD)[0];
        self::assertSame(12_000_00, $margin->revenueCents());
        self::assertSame(6_200_00, $margin->marginCents());
    }
}

/** Statut de clôture pilotable pour le test. */
final class InvoiceClosureStub implements PeriodClosureStatus
{
    public function __construct(public bool $closed)
    {
    }

    public function isClosed(TenantId $tenant, string $period): bool
    {
        return $this->closed;
    }
}
