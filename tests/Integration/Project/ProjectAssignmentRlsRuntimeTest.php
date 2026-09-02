<?php

declare(strict_types=1);

namespace App\Tests\Integration\Project;

use App\Domain\Project\ExceptionalImputationOpening;
use App\Domain\Project\ProjectAssignment;
use App\Domain\Tenant\TenantId;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use DateTimeImmutable;
use DateTimeZone;

/**
 * US-037 (T-037-06 / DBT-SEC-1) — la RLS isole affectations et ouvertures exceptionnelles au runtime
 * sous rôle non-superutilisateur : sans contexte de tenant, aucune ligne n'est visible.
 */
final class ProjectAssignmentRlsRuntimeTest extends KernelTestCase
{
    private const array RLS_TABLES = ['project_assignment', 'exceptional_imputation_opening'];
    private const string PROJECT = '018f9c4e-0000-7000-8000-0000000000bb';
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
            $this->em->getClassMetadata(ProjectAssignment::class),
            $this->em->getClassMetadata(ExceptionalImputationOpening::class),
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
        $this->connection->executeStatement('DROP ROLE IF EXISTS assignment_probe');
        $this->em->close();
        parent::tearDown();
    }

    public function testRlsIsolatesAssignmentTablesAtRuntime(): void
    {
        $tenantA = TenantId::generate();
        $tenantB = TenantId::generate();

        foreach ([$tenantA, $tenantB] as $tenant) {
            $this->em->persist(new ProjectAssignment($tenant, self::PROJECT, self::USER, 'Développeuse', 40));
            $this->em->persist(new ExceptionalImputationOpening($tenant, self::PROJECT, self::USER, new DateTimeImmutable('2026-10-05', new DateTimeZone('UTC')), 'Renfort', self::USER, new DateTimeImmutable('2026-10-01 09:00:00', new DateTimeZone('UTC'))));
        }
        $this->em->flush();
        $this->em->clear();

        $this->connection->executeStatement('DROP ROLE IF EXISTS assignment_probe');
        $this->connection->executeStatement('CREATE ROLE assignment_probe NOSUPERUSER');
        $this->connection->executeStatement('GRANT SELECT ON project_assignment, exceptional_imputation_opening TO assignment_probe');

        $this->connection->executeStatement(sprintf("SET app.current_tenant = '%s'", $tenantA->toString()));
        $this->connection->executeStatement('SET ROLE assignment_probe');
        $assignmentsForA = $this->countRows('project_assignment');
        $openingsForA = $this->countRows('exceptional_imputation_opening');

        $this->connection->executeStatement('RESET app.current_tenant');
        $assignmentsWithoutContext = $this->countRows('project_assignment');

        $this->connection->executeStatement('RESET ROLE');

        self::assertSame(1, $assignmentsForA, 'Seule l\'affectation du tenant A est visible.');
        self::assertSame(1, $openingsForA, 'Seule l\'ouverture du tenant A est visible.');
        self::assertSame(0, $assignmentsWithoutContext, 'Sans contexte, la RLS masque les affectations.');
    }

    private function countRows(string $table): int
    {
        $value = $this->connection->fetchOne(sprintf('SELECT COUNT(*) FROM %s', $table));

        return is_numeric($value) ? (int) $value : 0;
    }
}
