<?php

declare(strict_types=1);

namespace App\Tests\Integration\Tenant;

use App\Domain\Sample\ProtectedRecord;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * US-001 / TECH-2 (ENF-SEC-4) — la RLS isole les tenants **au runtime**, sous le rôle
 * applicatif non-superutilisateur, pilotée par le seul `SET app.current_tenant` (le
 * réglage qu'émet {@see \App\Infrastructure\Tenant\TenantSessionConfigurator}). Sans
 * contexte de tenant, la RLS masque toutes les lignes — filtre ORM non requis.
 */
final class TenantRlsRuntimeTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private Connection $connection;

    /** @var list<\Doctrine\ORM\Mapping\ClassMetadata<object>> */
    private array $schema;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->connection = $this->em->getConnection();

        $this->schema = [
            $this->em->getClassMetadata(Tenant::class),
            $this->em->getClassMetadata(ProtectedRecord::class),
        ];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        // RLS telle que posée par la migration Version20260831120000 (SchemaTool ne la crée pas).
        $this->connection->executeStatement('ALTER TABLE protected_record ENABLE ROW LEVEL SECURITY');
        $this->connection->executeStatement('ALTER TABLE protected_record FORCE ROW LEVEL SECURITY');
        $this->connection->executeStatement('DROP POLICY IF EXISTS tenant_isolation ON protected_record');
        $this->connection->executeStatement(
            "CREATE POLICY tenant_isolation ON protected_record USING (tenant_id::text = current_setting('app.current_tenant', true))",
        );
    }

    protected function tearDown(): void
    {
        $this->connection->executeStatement('RESET ROLE');
        $this->connection->executeStatement('RESET app.current_tenant');
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->connection->executeStatement('DROP ROLE IF EXISTS rls_runtime_probe');
        $this->em->close();
        parent::tearDown();
    }

    public function testRlsIsolatesTenantsAtRuntimeUnderAppRole(): void
    {
        $tenantA = TenantId::generate();
        $tenantB = TenantId::generate();
        $this->em->persist(new Tenant($tenantA, 'Agence A'));
        $this->em->persist(new Tenant($tenantB, 'Agence B'));
        $this->em->persist(new ProtectedRecord($tenantA, 'secret-A1'));
        $this->em->persist(new ProtectedRecord($tenantA, 'secret-A2'));
        $this->em->persist(new ProtectedRecord($tenantB, 'secret-B1'));
        $this->em->flush();
        $this->em->clear();

        $this->connection->executeStatement('DROP ROLE IF EXISTS rls_runtime_probe');
        $this->connection->executeStatement('CREATE ROLE rls_runtime_probe NOSUPERUSER');
        $this->connection->executeStatement('GRANT SELECT ON protected_record TO rls_runtime_probe');

        // Contexte tenant A (ce que pose le TenantSessionConfigurator), sous le rôle applicatif.
        $this->connection->executeStatement(sprintf("SET app.current_tenant = '%s'", $tenantA->toString()));
        $this->connection->executeStatement('SET ROLE rls_runtime_probe');
        $visibleForA = $this->countRecords();

        // Sans contexte : la RLS masque tout (aucune fuite hors tenant).
        $this->connection->executeStatement('RESET app.current_tenant');
        $visibleWithoutContext = $this->countRecords();

        $this->connection->executeStatement('RESET ROLE');

        self::assertSame(2, $visibleForA, 'Seuls les 2 enregistrements du tenant A sont visibles.');
        self::assertSame(0, $visibleWithoutContext, 'Sans app.current_tenant, la RLS masque toutes les lignes.');
    }

    private function countRecords(): int
    {
        $value = $this->connection->fetchOne('SELECT COUNT(*) FROM protected_record');
        if (!is_numeric($value)) {
            self::fail('Le COUNT n\'a pas renvoyé de valeur numérique.');
        }

        return (int) $value;
    }
}
