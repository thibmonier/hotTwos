<?php

declare(strict_types=1);

namespace App\Tests\Integration\Invoice;

use App\Domain\Invoice\Invoice;
use App\Domain\Tenant\TenantId;
use App\Infrastructure\Persistence\Doctrine\DoctrineInvoiceRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use DateTimeImmutable;
use DateTimeZone;

/**
 * US-075 (T-075-06) — persistance des factures : total facturé par période, liste par projet, et
 * **isolation tenant réelle** (RLS `invoice`) sous rôle non-superutilisateur.
 */
final class DoctrineInvoiceRepositoryTest extends KernelTestCase
{
    private const string PROJECT = '018f9c4e-0000-7000-8000-00000000aaaa';

    private EntityManagerInterface $em;
    private Connection $connection;
    private DoctrineInvoiceRepository $repository;

    /** @var list<\Doctrine\ORM\Mapping\ClassMetadata<object>> */
    private array $schema;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->connection = $this->em->getConnection();
        $this->repository = new DoctrineInvoiceRepository($this->em);

        $this->schema = [$this->em->getClassMetadata(Invoice::class)];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        $this->connection->executeStatement('RESET app.current_tenant');
        $this->connection->executeStatement('ALTER TABLE invoice ENABLE ROW LEVEL SECURITY');
        $this->connection->executeStatement('ALTER TABLE invoice FORCE ROW LEVEL SECURITY');
        $this->connection->executeStatement('DROP POLICY IF EXISTS tenant_isolation ON invoice');
        $this->connection->executeStatement(
            "CREATE POLICY tenant_isolation ON invoice USING (tenant_id::text = current_setting('app.current_tenant', true))",
        );
    }

    protected function tearDown(): void
    {
        $this->connection->executeStatement('RESET ROLE');
        $this->connection->executeStatement('RESET app.current_tenant');
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->connection->executeStatement('DROP ROLE IF EXISTS invoice_rls_probe');
        $this->em->close();
        parent::tearDown();
    }

    public function testTotalAndListForProject(): void
    {
        $tenant = TenantId::generate();
        $this->repository->save($this->invoice($tenant, '2026-11', 30_000_00));
        $this->repository->save($this->invoice($tenant, '2026-11', 12_000_00));
        $this->repository->save($this->invoice($tenant, '2026-10', 5_000_00));

        self::assertSame(42_000_00, $this->repository->totalForProjectPeriod($tenant, self::PROJECT, '2026-11'));
        self::assertSame(0, $this->repository->totalForProjectPeriod($tenant, self::PROJECT, '2026-09'));
        self::assertCount(3, $this->repository->findForProject($tenant, self::PROJECT));
        self::assertSame('2026-11', $this->repository->findForProject($tenant, self::PROJECT)[0]->period()); // tri période desc
    }

    public function testRlsRejectsCrossTenantWriteAndHidesRows(): void
    {
        $tenantA = TenantId::generate();
        $tenantB = TenantId::generate();

        $this->connection->executeStatement('DROP ROLE IF EXISTS invoice_rls_probe');
        $this->connection->executeStatement('CREATE ROLE invoice_rls_probe NOSUPERUSER');
        $this->connection->executeStatement('GRANT SELECT, INSERT ON invoice TO invoice_rls_probe');

        $this->connection->executeStatement("SET app.current_tenant = '".$tenantA->toString()."'");
        $this->connection->executeStatement('SET ROLE invoice_rls_probe');

        $this->insertRaw($tenantA);
        $rejected = false;
        try {
            $this->insertRaw($tenantB);
        } catch (\Doctrine\DBAL\Exception) {
            $rejected = true;
        }
        $visible = $this->connection->fetchOne('SELECT COUNT(*) FROM invoice');

        $this->connection->executeStatement('RESET ROLE');

        self::assertTrue($rejected, 'La RLS doit rejeter une écriture cross-tenant.');
        self::assertSame(1, is_numeric($visible) ? (int) $visible : -1);
    }

    private function invoice(TenantId $tenant, string $period, int $amount): Invoice
    {
        return Invoice::issue($tenant, self::PROJECT, $period, $amount, null, null, new DateTimeImmutable('2026-12-05 10:00:00', new DateTimeZone('UTC')));
    }

    private function insertRaw(TenantId $tenant): void
    {
        $this->connection->insert('invoice', [
            'id' => TenantId::generate()->toString(),
            'tenant_id' => $tenant->toString(),
            'project_ref' => self::PROJECT,
            'period' => '2026-11',
            'amount_cents' => 10_000_00,
            'client_id' => null,
            'issued_by' => null,
            'issued_at' => new DateTimeImmutable('2026-12-05 10:00:00', new DateTimeZone('UTC')),
            'status' => 'issued',
        ], ['issued_at' => 'datetime_immutable']);
    }
}
