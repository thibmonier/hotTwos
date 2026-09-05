<?php

declare(strict_types=1);

namespace App\Tests\Integration\Client;

use App\Domain\Client\Client;
use App\Domain\Tenant\TenantId;
use App\Infrastructure\Persistence\Doctrine\DoctrineClientRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * US-014 (T-014-05) — persistance des clients contre une vraie base : lecture par tenant et
 * **isolation tenant réelle** (RLS `client`) sous rôle non-superutilisateur.
 */
final class DoctrineClientRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private Connection $connection;
    private DoctrineClientRepository $repository;

    /** @var list<\Doctrine\ORM\Mapping\ClassMetadata<object>> */
    private array $schema;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->connection = $this->em->getConnection();
        $this->repository = new DoctrineClientRepository($this->em);

        $this->schema = [$this->em->getClassMetadata(Client::class)];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        $this->connection->executeStatement('RESET app.current_tenant');
        $this->connection->executeStatement('ALTER TABLE client ENABLE ROW LEVEL SECURITY');
        $this->connection->executeStatement('ALTER TABLE client FORCE ROW LEVEL SECURITY');
        $this->connection->executeStatement('DROP POLICY IF EXISTS tenant_isolation ON client');
        $this->connection->executeStatement(
            "CREATE POLICY tenant_isolation ON client USING (tenant_id::text = current_setting('app.current_tenant', true))",
        );
    }

    protected function tearDown(): void
    {
        $this->connection->executeStatement('RESET ROLE');
        $this->connection->executeStatement('RESET app.current_tenant');
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->connection->executeStatement('DROP ROLE IF EXISTS client_rls_probe');
        $this->em->close();
        parent::tearDown();
    }

    public function testSavesAndListsClientsForTenant(): void
    {
        $tenant = TenantId::generate();
        $this->repository->save(new Client($tenant, 'ACME Tourisme', '123456789'));
        $this->repository->save(new Client($tenant, 'Globex'));

        $clients = $this->repository->findAllByTenant($tenant);
        self::assertCount(2, $clients);
        self::assertSame('ACME Tourisme', $clients[0]->name()); // trié par nom
        self::assertSame('Globex', $clients[1]->name());
        self::assertSame([], $this->repository->findAllByTenant(TenantId::generate()));
    }

    public function testRlsRejectsCrossTenantWriteAndHidesRows(): void
    {
        $tenantA = TenantId::generate();
        $tenantB = TenantId::generate();

        $this->connection->executeStatement('DROP ROLE IF EXISTS client_rls_probe');
        $this->connection->executeStatement('CREATE ROLE client_rls_probe NOSUPERUSER');
        $this->connection->executeStatement('GRANT SELECT, INSERT ON client TO client_rls_probe');

        $this->connection->executeStatement("SET app.current_tenant = '".$tenantA->toString()."'");
        $this->connection->executeStatement('SET ROLE client_rls_probe');

        $this->insertRaw($tenantA, 'ACME');
        $rejected = false;
        try {
            $this->insertRaw($tenantB, 'Globex');
        } catch (\Doctrine\DBAL\Exception) {
            $rejected = true;
        }
        $visible = $this->connection->fetchOne('SELECT COUNT(*) FROM client');

        $this->connection->executeStatement('RESET ROLE');

        self::assertTrue($rejected, 'La RLS doit rejeter une écriture cross-tenant.');
        self::assertSame(1, is_numeric($visible) ? (int) $visible : -1);
    }

    private function insertRaw(TenantId $tenant, string $name): void
    {
        $this->connection->insert('client', [
            'id' => TenantId::generate()->toString(),
            'tenant_id' => $tenant->toString(),
            'name' => $name,
            'siren' => null,
        ]);
    }
}
