<?php

declare(strict_types=1);

namespace App\Tests\Integration\Project;

use App\Domain\Project\ProjectReopening;
use App\Domain\Tenant\TenantId;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use DateTimeImmutable;
use DateTimeZone;

/**
 * US-038 (T-038-06 / DBT-SEC-1) — la RLS isole les réouvertures de projet au runtime sous rôle
 * non-superutilisateur : sans contexte de tenant, aucune ligne n'est visible.
 */
final class ProjectReopeningRlsRuntimeTest extends KernelTestCase
{
    private const string PROJECT = '018f9c4e-0000-7000-8000-0000000000bb';

    private EntityManagerInterface $em;
    private Connection $connection;

    /** @var list<\Doctrine\ORM\Mapping\ClassMetadata<object>> */
    private array $schema;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->connection = $this->em->getConnection();

        $this->schema = [$this->em->getClassMetadata(ProjectReopening::class)];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        $this->connection->executeStatement('ALTER TABLE project_reopening ENABLE ROW LEVEL SECURITY');
        $this->connection->executeStatement('ALTER TABLE project_reopening FORCE ROW LEVEL SECURITY');
        $this->connection->executeStatement('DROP POLICY IF EXISTS tenant_isolation ON project_reopening');
        $this->connection->executeStatement(
            "CREATE POLICY tenant_isolation ON project_reopening USING (tenant_id::text = current_setting('app.current_tenant', true))",
        );
    }

    protected function tearDown(): void
    {
        $this->connection->executeStatement('RESET ROLE');
        $this->connection->executeStatement('RESET app.current_tenant');
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->connection->executeStatement('DROP ROLE IF EXISTS reopening_probe');
        $this->em->close();
        parent::tearDown();
    }

    public function testRlsIsolatesReopeningsAtRuntime(): void
    {
        $tenantA = TenantId::generate();
        $tenantB = TenantId::generate();

        foreach ([$tenantA, $tenantB] as $tenant) {
            $this->em->persist(new ProjectReopening($tenant, self::PROJECT, self::PROJECT, 'Régularisation', new DateTimeImmutable('2026-11-01 09:00:00', new DateTimeZone('UTC'))));
        }
        $this->em->flush();
        $this->em->clear();

        $this->connection->executeStatement('DROP ROLE IF EXISTS reopening_probe');
        $this->connection->executeStatement('CREATE ROLE reopening_probe NOSUPERUSER');
        $this->connection->executeStatement('GRANT SELECT ON project_reopening TO reopening_probe');

        $this->connection->executeStatement(sprintf("SET app.current_tenant = '%s'", $tenantA->toString()));
        $this->connection->executeStatement('SET ROLE reopening_probe');
        $forA = $this->countRows();

        $this->connection->executeStatement('RESET app.current_tenant');
        $withoutContext = $this->countRows();

        $this->connection->executeStatement('RESET ROLE');

        self::assertSame(1, $forA, 'Seule la réouverture du tenant A est visible.');
        self::assertSame(0, $withoutContext, 'Sans contexte, la RLS masque les réouvertures.');
    }

    private function countRows(): int
    {
        $value = $this->connection->fetchOne('SELECT COUNT(*) FROM project_reopening');

        return is_numeric($value) ? (int) $value : 0;
    }
}
