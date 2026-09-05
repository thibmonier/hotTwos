<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Fec;

use App\Application\Authorization\Authorizer;
use App\Application\Fec\ExportFec;
use App\Domain\Authorization\AccessDeniedException;
use App\Domain\Authorization\DataScope;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\Role;
use App\Domain\Fec\FecConfiguration;
use App\Domain\Fec\FecExportException;
use App\Domain\Fec\FecGenerator;
use App\Domain\Margin\ProjectMargin;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Domain\Valuation\PeriodClosureStatus;
use App\Tests\Support\Authorization\InMemoryRoleRepository;
use App\Tests\Support\Authorization\RecordingSecurityAuditLogger;
use App\Tests\Support\Fec\InMemoryFecConfigurationRepository;
use App\Tests\Support\Margin\InMemoryProjectMarginRepository;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

/**
 * US-074 (T-074-05/07, CA-3/CA-4) — export FEC : gating HAB-1 + trace HAB-6, période clôturée requise,
 * configuration comptable requise.
 */
final class ExportFecTest extends TestCase
{
    private const string PERIOD = '2026-11';
    private const string PROJECT = '018f9c4e-0000-7000-8000-00000000aaaa';

    private TenantId $tenant;
    private InMemoryFecConfigurationRepository $configs;
    private InMemoryProjectMarginRepository $margins;
    private RecordingSecurityAuditLogger $audit;
    private ClosureStub $closure;
    private ExportFec $export;
    private User $finance;
    private User $projectChief;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();

        $roles = new InMemoryRoleRepository();
        $roles->add(new Role($this->tenant, 'Finance', [Permission::VIEW_PROJECT_FINANCIALS, Permission::VIEW_COLLABORATOR_COST], DataScope::TENANT));
        $roles->add(new Role($this->tenant, 'ChefProjet', [Permission::VIEW_PROJECT_FINANCIALS], DataScope::TENANT));

        $this->configs = new InMemoryFecConfigurationRepository();
        $this->configs->save(new FecConfiguration(
            $this->tenant,
            '123456789',
            'VT',
            'Ventes',
            '706000',
            'Prestations',
            '411000',
            'Clients',
            '641000',
            'Rémunérations',
            '791000',
            'Transferts de charges',
        ));

        $this->margins = new InMemoryProjectMarginRepository();
        $this->margins->replaceForPeriod($this->tenant, self::PERIOD, [
            ProjectMargin::freeze($this->tenant, self::PERIOD, self::PROJECT, 'Site vitrine', 10_000_00, 5_800_00, 10, 0, new DateTimeImmutable('2026-12-01 09:00:00', new DateTimeZone('UTC'))),
        ]);

        $this->closure = new ClosureStub(true);
        $this->audit = new RecordingSecurityAuditLogger();
        $this->export = new ExportFec(
            new Authorizer($roles, $this->audit),
            $this->closure,
            $this->configs,
            $this->margins,
            new FecGenerator(),
        );

        $this->finance = new User($this->tenant, 'finance@agence.test', 'hash', ['Finance']);
        $this->projectChief = new User($this->tenant, 'chef@agence.test', 'hash', ['ChefProjet']);
    }

    public function testFinanceExportsFecFileAndReadIsTraced(): void
    {
        $export = $this->export->forPeriod($this->finance, self::PERIOD);

        self::assertSame('123456789FEC20261130.txt', $export->fileName);
        self::assertStringContainsString('JournalCode', $export->content);
        self::assertStringContainsString('706000', $export->content);
        self::assertTrue($this->audit->has('sensitive_data_read'));
    }

    public function testProjectChiefWithoutCostPermissionIsDenied(): void
    {
        // Le FEC contient des coûts → VIEW_COLLABORATOR_COST requis (HAB-1).
        $this->expectException(AccessDeniedException::class);

        $this->export->forPeriod($this->projectChief, self::PERIOD);
    }

    public function testOpenPeriodIsRefused(): void
    {
        $this->closure->closed = false;

        $this->expectException(FecExportException::class);
        $this->export->forPeriod($this->finance, self::PERIOD);
    }

    public function testMissingConfigurationIsRefused(): void
    {
        $this->configs->configs = [];

        $this->expectException(FecExportException::class);
        $this->export->forPeriod($this->finance, self::PERIOD);
    }
}

/** Stub de statut de clôture pilotable. */
final class ClosureStub implements PeriodClosureStatus
{
    public function __construct(public bool $closed)
    {
    }

    public function isClosed(TenantId $tenant, string $period): bool
    {
        return $this->closed;
    }
}
