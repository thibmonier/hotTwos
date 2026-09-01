<?php

declare(strict_types=1);

namespace App\Tests\Integration\Organization;

use App\Domain\Organization\OrgMembership;
use App\Domain\Organization\OrgUnit;
use App\Domain\Shared\EffectivePeriod;
use App\Domain\Tenant\TenantId;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * US-010 (T-010-07 / DBT-SEC-1) — la RLS isole les tables métier de l'organisation au runtime,
 * sous le rôle applicatif non-superutilisateur, pilotée par le seul `SET app.current_tenant`.
 * Sans contexte de tenant, aucune ligne n'est visible (test d'intrusion).
 */
final class OrgRlsRuntimeTest extends KernelTestCase
{
    private const array RLS_TABLES = ['org_unit', 'org_membership'];

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
            $this->em->getClassMetadata(OrgUnit::class),
            $this->em->getClassMetadata(OrgMembership::class),
        ];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        // RLS telle que posée par les migrations Version20260901130000 / 140000 (SchemaTool ne la crée pas).
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
        $this->connection->executeStatement('DROP ROLE IF EXISTS org_rls_probe');
        $this->em->close();
        parent::tearDown();
    }

    public function testRlsIsolatesOrganizationTablesAtRuntime(): void
    {
        $tenantA = TenantId::generate();
        $tenantB = TenantId::generate();

        $unitA1 = new OrgUnit($tenantA, null, 'A-1');
        $unitA2 = new OrgUnit($tenantA, null, 'A-2');
        $unitB1 = new OrgUnit($tenantB, null, 'B-1');
        $this->em->persist($unitA1);
        $this->em->persist($unitA2);
        $this->em->persist($unitB1);
        $this->em->persist(new OrgMembership($tenantA, TenantId::generate()->toString(), $unitA1->id(), $this->period()));
        $this->em->persist(new OrgMembership($tenantB, TenantId::generate()->toString(), $unitB1->id(), $this->period()));
        $this->em->flush();
        $this->em->clear();

        $this->connection->executeStatement('DROP ROLE IF EXISTS org_rls_probe');
        $this->connection->executeStatement('CREATE ROLE org_rls_probe NOSUPERUSER');
        $this->connection->executeStatement('GRANT SELECT ON org_unit, org_membership TO org_rls_probe');

        $this->connection->executeStatement(sprintf("SET app.current_tenant = '%s'", $tenantA->toString()));
        $this->connection->executeStatement('SET ROLE org_rls_probe');
        $unitsForA = $this->countRows('org_unit');
        $membershipsForA = $this->countRows('org_membership');

        $this->connection->executeStatement('RESET app.current_tenant');
        $unitsWithoutContext = $this->countRows('org_unit');
        $membershipsWithoutContext = $this->countRows('org_membership');

        $this->connection->executeStatement('RESET ROLE');

        self::assertSame(2, $unitsForA, 'Seules les 2 unités du tenant A sont visibles.');
        self::assertSame(1, $membershipsForA, 'Seul le rattachement du tenant A est visible.');
        self::assertSame(0, $unitsWithoutContext, 'Sans contexte, la RLS masque les unités.');
        self::assertSame(0, $membershipsWithoutContext, 'Sans contexte, la RLS masque les rattachements.');
    }

    private function countRows(string $table): int
    {
        $value = $this->connection->fetchOne(sprintf('SELECT COUNT(*) FROM %s', $table));
        if (!is_numeric($value)) {
            self::fail('Le COUNT n\'a pas renvoyé de valeur numérique.');
        }

        return (int) $value;
    }

    private function period(): EffectivePeriod
    {
        return EffectivePeriod::since(new DateTimeImmutable('2026-01-01 00:00:00', new DateTimeZone('UTC')));
    }
}
