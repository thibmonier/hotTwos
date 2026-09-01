<?php

declare(strict_types=1);

namespace App\Tests\Integration\Timesheet;

use App\Domain\Project\Project;
use App\Domain\Tenant\TenantId;
use App\Domain\Timesheet\TimeEntry;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use DateTimeImmutable;
use DateTimeZone;

/**
 * T-TECH-02 (DBT-SEC-1) — la RLS isole les tables métier `project` et `time_entry` au runtime,
 * sous le rôle applicatif non-superutilisateur. Sans contexte de tenant, aucune ligne n'est
 * visible ; avec un contexte, seules les lignes du tenant courant le sont (test d'intrusion).
 */
final class TimesheetRlsRuntimeTest extends KernelTestCase
{
    private const array RLS_TABLES = ['project', 'time_entry'];
    private const string USER = '018f9c4e-0000-7000-8000-0000000000aa';

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
            $this->em->getClassMetadata(Project::class),
            $this->em->getClassMetadata(TimeEntry::class),
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
        $this->connection->executeStatement('DROP ROLE IF EXISTS timesheet_rls_probe');
        $this->em->close();
        parent::tearDown();
    }

    public function testRlsIsolatesProjectAndTimeEntryAtRuntime(): void
    {
        $tenantA = TenantId::generate();
        $tenantB = TenantId::generate();

        $projectA = new Project($tenantA, 'PRJ-A', 'Projet A');
        $projectB = new Project($tenantB, 'PRJ-B', 'Projet B');
        $this->em->persist($projectA);
        $this->em->persist($projectB);
        $this->em->persist(new TimeEntry($tenantA, self::USER, $projectA->id(), $this->day(), 420));
        $this->em->persist(new TimeEntry($tenantB, self::USER, $projectB->id(), $this->day(), 210));
        $this->em->flush();
        $this->em->clear();

        $this->connection->executeStatement('DROP ROLE IF EXISTS timesheet_rls_probe');
        $this->connection->executeStatement('CREATE ROLE timesheet_rls_probe NOSUPERUSER');
        $this->connection->executeStatement('GRANT SELECT ON project, time_entry TO timesheet_rls_probe');

        $this->connection->executeStatement(sprintf("SET app.current_tenant = '%s'", $tenantA->toString()));
        $this->connection->executeStatement('SET ROLE timesheet_rls_probe');
        $projectsForA = $this->countRows('project');
        $entriesForA = $this->countRows('time_entry');

        $this->connection->executeStatement('RESET app.current_tenant');
        $projectsWithoutContext = $this->countRows('project');
        $entriesWithoutContext = $this->countRows('time_entry');

        $this->connection->executeStatement('RESET ROLE');

        self::assertSame(1, $projectsForA, 'Seul le projet du tenant A est visible.');
        self::assertSame(1, $entriesForA, 'Seule l\'imputation du tenant A est visible.');
        self::assertSame(0, $projectsWithoutContext, 'Sans contexte, la RLS masque les projets.');
        self::assertSame(0, $entriesWithoutContext, 'Sans contexte, la RLS masque les imputations.');
    }

    private function countRows(string $table): int
    {
        $value = $this->connection->fetchOne(sprintf('SELECT COUNT(*) FROM %s', $table));

        return is_numeric($value) ? (int) $value : 0;
    }

    private function day(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-08-15 00:00:00', new DateTimeZone('UTC'));
    }
}
