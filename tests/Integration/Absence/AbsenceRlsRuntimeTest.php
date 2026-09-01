<?php

declare(strict_types=1);

namespace App\Tests\Integration\Absence;

use App\Domain\Absence\AbsenceRequest;
use App\Domain\Absence\AbsenceType;
use App\Domain\Tenant\TenantId;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use DateTimeImmutable;
use DateTimeZone;

/**
 * US-054 (T-054-07 / DBT-SEC-1) — la RLS isole les tables d'absence au runtime, sous le rôle
 * applicatif non-superutilisateur : sans contexte de tenant, aucune ligne n'est visible.
 */
final class AbsenceRlsRuntimeTest extends KernelTestCase
{
    private const array RLS_TABLES = ['absence_type', 'absence_request'];
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
            $this->em->getClassMetadata(AbsenceType::class),
            $this->em->getClassMetadata(AbsenceRequest::class),
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
        $this->connection->executeStatement('DROP ROLE IF EXISTS absence_rls_probe');
        $this->em->close();
        parent::tearDown();
    }

    public function testRlsIsolatesAbsenceTablesAtRuntime(): void
    {
        $tenantA = TenantId::generate();
        $tenantB = TenantId::generate();

        $typeA = new AbsenceType($tenantA, 'Congés payés');
        $typeB = new AbsenceType($tenantB, 'Congés payés');
        $this->em->persist($typeA);
        $this->em->persist($typeB);
        $this->em->persist($this->request($tenantA, $typeA->id()));
        $this->em->persist($this->request($tenantB, $typeB->id()));
        $this->em->flush();
        $this->em->clear();

        $this->connection->executeStatement('DROP ROLE IF EXISTS absence_rls_probe');
        $this->connection->executeStatement('CREATE ROLE absence_rls_probe NOSUPERUSER');
        $this->connection->executeStatement('GRANT SELECT ON absence_type, absence_request TO absence_rls_probe');

        $this->connection->executeStatement(sprintf("SET app.current_tenant = '%s'", $tenantA->toString()));
        $this->connection->executeStatement('SET ROLE absence_rls_probe');
        $typesForA = $this->countRows('absence_type');
        $requestsForA = $this->countRows('absence_request');

        $this->connection->executeStatement('RESET app.current_tenant');
        $typesWithoutContext = $this->countRows('absence_type');
        $requestsWithoutContext = $this->countRows('absence_request');

        $this->connection->executeStatement('RESET ROLE');

        self::assertSame(1, $typesForA, 'Seul le type du tenant A est visible.');
        self::assertSame(1, $requestsForA, 'Seule la demande du tenant A est visible.');
        self::assertSame(0, $typesWithoutContext, 'Sans contexte, la RLS masque les types.');
        self::assertSame(0, $requestsWithoutContext, 'Sans contexte, la RLS masque les demandes.');
    }

    private function request(TenantId $tenant, string $typeId): AbsenceRequest
    {
        return new AbsenceRequest($tenant, self::USER, $typeId, $this->day('2026-09-01'), $this->day('2026-09-05'), true, true, $this->day('2026-08-01'));
    }

    private function countRows(string $table): int
    {
        $value = $this->connection->fetchOne(sprintf('SELECT COUNT(*) FROM %s', $table));

        return is_numeric($value) ? (int) $value : 0;
    }

    private function day(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value.' 00:00:00', new DateTimeZone('UTC'));
    }
}
