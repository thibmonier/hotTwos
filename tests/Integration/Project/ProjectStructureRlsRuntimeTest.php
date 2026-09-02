<?php

declare(strict_types=1);

namespace App\Tests\Integration\Project;

use App\Domain\Project\ProjectLot;
use App\Domain\Project\ProjectMilestone;
use App\Domain\Tenant\TenantId;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use DateTimeImmutable;
use DateTimeZone;

/**
 * US-031 (T-031-06 / DBT-SEC-1) — la RLS isole lots et jalons au runtime sous rôle non-superutilisateur :
 * sans contexte de tenant, aucune ligne n'est visible.
 */
final class ProjectStructureRlsRuntimeTest extends KernelTestCase
{
    private const array RLS_TABLES = ['project_lot', 'project_milestone'];
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

        $this->schema = [
            $this->em->getClassMetadata(ProjectLot::class),
            $this->em->getClassMetadata(ProjectMilestone::class),
        ];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        foreach (self::RLS_TABLES as $table) {
            $this->connection->executeStatement(sprintf('ALTER TABLE %s ENABLE ROW LEVEL SECURITY', $table));
            $this->connection->executeStatement(sprintf('ALTER TABLE %s FORCE ROW LEVEL SECURITY', $table));
            $this->connection->executeStatement(sprintf('DROP POLICY IF EXISTS tenant_isolation ON %s', $table));
            $this->connection->executeStatement(sprintf(
                "CREATE POLICY tenant_isolation ON %s USING (tenant_id::text = current_setting('app.current_tenant', true))",
                $table,
            ));
        }
    }

    protected function tearDown(): void
    {
        $this->connection->executeStatement('RESET ROLE');
        $this->connection->executeStatement('RESET app.current_tenant');
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->connection->executeStatement('DROP ROLE IF EXISTS project_struct_probe');
        $this->em->close();
        parent::tearDown();
    }

    public function testRlsIsolatesStructureTablesAtRuntime(): void
    {
        $tenantA = TenantId::generate();
        $tenantB = TenantId::generate();

        foreach ([$tenantA, $tenantB] as $tenant) {
            $this->em->persist(new ProjectLot($tenant, self::PROJECT, 'Analyse', 40, 3_200_000));
            $this->em->persist(new ProjectMilestone($tenant, self::PROJECT, 'Recette', new DateTimeImmutable('2027-02-28', new DateTimeZone('UTC')), 6_000_000));
        }
        $this->em->flush();
        $this->em->clear();

        $this->connection->executeStatement('DROP ROLE IF EXISTS project_struct_probe');
        $this->connection->executeStatement('CREATE ROLE project_struct_probe NOSUPERUSER');
        $this->connection->executeStatement('GRANT SELECT ON project_lot, project_milestone TO project_struct_probe');

        $this->connection->executeStatement(sprintf("SET app.current_tenant = '%s'", $tenantA->toString()));
        $this->connection->executeStatement('SET ROLE project_struct_probe');
        $lotsForA = $this->countRows('project_lot');
        $milestonesForA = $this->countRows('project_milestone');

        $this->connection->executeStatement('RESET app.current_tenant');
        $lotsWithoutContext = $this->countRows('project_lot');

        $this->connection->executeStatement('RESET ROLE');

        self::assertSame(1, $lotsForA, 'Seul le lot du tenant A est visible.');
        self::assertSame(1, $milestonesForA, 'Seul le jalon du tenant A est visible.');
        self::assertSame(0, $lotsWithoutContext, 'Sans contexte, la RLS masque les lots.');
    }

    private function countRows(string $table): int
    {
        $value = $this->connection->fetchOne(sprintf('SELECT COUNT(*) FROM %s', $table));

        return is_numeric($value) ? (int) $value : 0;
    }
}
