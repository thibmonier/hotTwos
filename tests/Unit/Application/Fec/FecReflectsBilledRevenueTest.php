<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Fec;

use App\Application\Authorization\Authorizer;
use App\Application\Fec\ExportFec;
use App\Application\Margin\ComputeProjectMargins;
use App\Domain\Authorization\DataScope;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\Role;
use App\Domain\Fec\FecConfiguration;
use App\Domain\Fec\FecGenerator;
use App\Domain\Invoice\Invoice;
use App\Domain\Margin\ProjectMargin;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Domain\Valuation\PeriodClosureStatus;
use App\Domain\Valuation\ProjectValuationLine;
use App\Infrastructure\Margin\InvoiceRevenueSource;
use App\Tests\Support\Authorization\InMemoryRoleRepository;
use App\Tests\Support\Authorization\RecordingSecurityAuditLogger;
use App\Tests\Support\Fec\InMemoryFecConfigurationRepository;
use App\Tests\Support\Invoice\InMemoryInvoiceRepository;
use App\Tests\Support\Margin\InMemoryProjectMarginRepository;
use App\Tests\Support\Valuation\InMemoryTimeEntryValuationRepository;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

/**
 * US-077 (CA-1/CA-2/CA-3, ADR-0022) — l'export FEC reflète le **facturé réel** via la source de revenu
 * unique (US-076), avec repli sur le CA reconnu. La chaîne réelle est exercée de bout en bout :
 * {@see ComputeProjectMargins} fige la marge à partir de {@see InvoiceRevenueSource}, puis
 * {@see ExportFec}/{@see FecGenerator} lisent cette même marge figée → aucune divergence possible.
 */
final class FecReflectsBilledRevenueTest extends TestCase
{
    private const string PERIOD = '2026-11';
    private const string PROJECT = '018f9c4e-0000-7000-8000-00000000aaaa';

    private TenantId $tenant;
    private InMemoryTimeEntryValuationRepository $valuations;
    private InMemoryProjectMarginRepository $margins;
    private InMemoryInvoiceRepository $invoices;
    private FecConfiguration $config;
    private FecGenerator $generator;
    private ComputeProjectMargins $compute;
    private ExportFec $export;
    private User $finance;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();

        $roles = new InMemoryRoleRepository();
        $roles->add(new Role($this->tenant, 'Finance', [Permission::VIEW_PROJECT_FINANCIALS, Permission::VIEW_COLLABORATOR_COST], DataScope::TENANT));

        $this->config = new FecConfiguration(
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
        );
        $configs = new InMemoryFecConfigurationRepository();
        $configs->save($this->config);

        $this->valuations = new InMemoryTimeEntryValuationRepository();
        $this->margins = new InMemoryProjectMarginRepository();
        $this->invoices = new InMemoryInvoiceRepository();
        $this->generator = new FecGenerator();
        $clock = new MockClock(new DateTimeImmutable('2026-12-01 09:00:00', new DateTimeZone('UTC')));

        $this->compute = new ComputeProjectMargins(
            $this->valuations,
            $this->margins,
            new InvoiceRevenueSource($this->invoices),
            $clock,
        );

        $audit = new RecordingSecurityAuditLogger();
        $this->export = new ExportFec(
            new Authorizer($roles, $audit),
            new AlwaysClosedStub(),
            $configs,
            $this->margins,
            $this->generator,
        );

        $this->finance = new User($this->tenant, 'finance@agence.test', 'hash', ['Finance']);
    }

    public function testFecUsesBilledRevenueWhenInvoicePresent(): void
    {
        // CA reconnu (valorisation) = 10 000, mais facturé réel = 12 000.
        $this->valuations->projectBreakdownForPeriod = [
            new ProjectValuationLine(self::PROJECT, 'Site vitrine', 12, 10_000_00, 0),
        ];
        $this->invoices->save(Invoice::issue($this->tenant, self::PROJECT, self::PERIOD, 12_000_00, null, null, new DateTimeImmutable('2026-12-05', new DateTimeZone('UTC'))));
        $this->compute->forClosedPeriod($this->tenant, self::PERIOD);

        $content = $this->export->forPeriod($this->finance, self::PERIOD)->content;

        self::assertStringContainsString('12000,00', $content);        // facturé réel dans le FEC
        self::assertStringNotContainsString('10000,00', $content);     // pas le CA reconnu
    }

    public function testFecFallsBackToRecognizedRevenueWithoutInvoice(): void
    {
        $this->valuations->projectBreakdownForPeriod = [
            new ProjectValuationLine(self::PROJECT, 'Site vitrine', 12, 10_000_00, 0),
        ];
        $this->compute->forClosedPeriod($this->tenant, self::PERIOD);

        $content = $this->export->forPeriod($this->finance, self::PERIOD)->content;

        self::assertStringContainsString('10000,00', $content);        // repli CA reconnu
    }

    public function testFecRevenueMatchesFrozenMarginRevenue(): void
    {
        // Cohérence FEC ↔ marge : même source de revenu (US-076), jamais divergents.
        $this->valuations->projectBreakdownForPeriod = [
            new ProjectValuationLine(self::PROJECT, 'Site vitrine', 12, 10_000_00, 0),
        ];
        $this->invoices->save(Invoice::issue($this->tenant, self::PROJECT, self::PERIOD, 12_000_00, null, null, new DateTimeImmutable('2026-12-05', new DateTimeZone('UTC'))));
        $this->compute->forClosedPeriod($this->tenant, self::PERIOD);

        $frozen = $this->margins->findForPeriod($this->tenant, self::PERIOD)[0];
        $revenueLine = $this->revenueCreditLine($frozen);

        self::assertSame(number_format($frozen->revenueCents() / 100, 2, ',', ''), $revenueLine->credit);
    }

    public function testRevenueEntryLabelIsSourceNeutral(): void
    {
        // Le libellé ne doit pas affirmer « CA reconnu » quand le montant est le facturé réel.
        $this->valuations->projectBreakdownForPeriod = [
            new ProjectValuationLine(self::PROJECT, 'Site vitrine', 12, 10_000_00, 0),
        ];
        $this->invoices->save(Invoice::issue($this->tenant, self::PROJECT, self::PERIOD, 12_000_00, null, null, new DateTimeImmutable('2026-12-05', new DateTimeZone('UTC'))));
        $this->compute->forClosedPeriod($this->tenant, self::PERIOD);

        $content = $this->export->forPeriod($this->finance, self::PERIOD)->content;

        self::assertStringContainsString('Revenu retenu Site vitrine '.self::PERIOD, $content);
        self::assertStringNotContainsString('CA reconnu', $content);
    }

    private function revenueCreditLine(ProjectMargin $margin): \App\Domain\Fec\FecLine
    {
        foreach ($this->generator->lines($this->config, self::PERIOD, [$margin]) as $line) {
            if ($line->compteNum === $this->config->revenueAccountNum() && '' !== $line->credit) {
                return $line;
            }
        }

        self::fail('Aucune ligne de crédit produit générée.');
    }
}

final class AlwaysClosedStub implements PeriodClosureStatus
{
    public function isClosed(TenantId $tenant, string $period): bool
    {
        return true;
    }
}
