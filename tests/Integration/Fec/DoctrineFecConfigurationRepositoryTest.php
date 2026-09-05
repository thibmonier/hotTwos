<?php

declare(strict_types=1);

namespace App\Tests\Integration\Fec;

use App\Domain\Fec\FecConfiguration;
use App\Domain\Tenant\TenantId;
use App\Infrastructure\Persistence\Doctrine\DoctrineFecConfigurationRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * US-074 (revue T-074-09) — persistance de la config FEC contre une vraie base : une config par
 * tenant (findForTenant) et **isolation tenant réelle** (RLS `fec_configuration`) sous rôle
 * non-superutilisateur — la policy rejette toute écriture/lecture cross-tenant sans `app.current_tenant`.
 */
final class DoctrineFecConfigurationRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private Connection $connection;
    private DoctrineFecConfigurationRepository $repository;

    /** @var list<\Doctrine\ORM\Mapping\ClassMetadata<object>> */
    private array $schema;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->connection = $this->em->getConnection();
        $this->repository = new DoctrineFecConfigurationRepository($this->em);

        $this->schema = [$this->em->getClassMetadata(FecConfiguration::class)];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        $this->connection->executeStatement('RESET app.current_tenant');
        $this->connection->executeStatement('ALTER TABLE fec_configuration ENABLE ROW LEVEL SECURITY');
        $this->connection->executeStatement('ALTER TABLE fec_configuration FORCE ROW LEVEL SECURITY');
        $this->connection->executeStatement('DROP POLICY IF EXISTS tenant_isolation ON fec_configuration');
        $this->connection->executeStatement(
            "CREATE POLICY tenant_isolation ON fec_configuration USING (tenant_id::text = current_setting('app.current_tenant', true))",
        );
    }

    protected function tearDown(): void
    {
        $this->connection->executeStatement('RESET ROLE');
        $this->connection->executeStatement('RESET app.current_tenant');
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->connection->executeStatement('DROP ROLE IF EXISTS fec_rls_probe');
        $this->em->close();
        parent::tearDown();
    }

    public function testSavesAndReadsConfigurationForTenant(): void
    {
        $tenant = TenantId::generate();
        $this->repository->save($this->config($tenant));

        $found = $this->repository->findForTenant($tenant);
        self::assertNotNull($found);
        self::assertSame('123456789', $found->siren());
        self::assertSame('706000', $found->revenueAccountNum());

        // Aucune config pour un autre tenant.
        self::assertNull($this->repository->findForTenant(TenantId::generate()));
    }

    public function testRlsRejectsCrossTenantWriteAndHidesCrossTenantRows(): void
    {
        $tenantA = TenantId::generate();
        $tenantB = TenantId::generate();

        $this->connection->executeStatement('DROP ROLE IF EXISTS fec_rls_probe');
        $this->connection->executeStatement('CREATE ROLE fec_rls_probe NOSUPERUSER');
        $this->connection->executeStatement('GRANT SELECT, INSERT ON fec_configuration TO fec_rls_probe');

        $this->connection->executeStatement("SET app.current_tenant = '".$tenantA->toString()."'");
        $this->connection->executeStatement('SET ROLE fec_rls_probe');

        $this->insertRaw($tenantA);
        $rejected = false;
        try {
            $this->insertRaw($tenantB);
        } catch (\Doctrine\DBAL\Exception) {
            $rejected = true;
        }
        $visible = $this->connection->fetchOne('SELECT COUNT(*) FROM fec_configuration');

        $this->connection->executeStatement('RESET ROLE');

        self::assertTrue($rejected, 'La RLS doit rejeter une écriture cross-tenant.');
        self::assertSame(1, is_numeric($visible) ? (int) $visible : -1);
    }

    private function insertRaw(TenantId $tenant): void
    {
        $this->connection->insert('fec_configuration', [
            'id' => TenantId::generate()->toString(),
            'tenant_id' => $tenant->toString(),
            'siren' => '123456789',
            'journal_code' => 'VT',
            'journal_lib' => 'Ventes',
            'revenue_account_num' => '706000',
            'revenue_account_lib' => 'Prestations',
            'receivable_account_num' => '411000',
            'receivable_account_lib' => 'Clients',
            'cost_account_num' => '641000',
            'cost_account_lib' => 'Rémunérations',
            'cost_counterpart_account_num' => '791000',
            'cost_counterpart_account_lib' => 'Transferts de charges',
        ]);
    }

    private function config(TenantId $tenant): FecConfiguration
    {
        return new FecConfiguration(
            $tenant,
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
    }
}
