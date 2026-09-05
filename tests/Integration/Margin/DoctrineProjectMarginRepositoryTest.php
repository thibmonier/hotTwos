<?php

declare(strict_types=1);

namespace App\Tests\Integration\Margin;

use App\Domain\Margin\ProjectMargin;
use App\Domain\Tenant\TenantId;
use App\Infrastructure\Persistence\Doctrine\DoctrineProjectMarginRepository;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * US-071 (T-071-07, revue T-071-09) — persistance des marges figées contre une vraie base :
 * remplacement atomique par période (INV-2), tri du CA décroissant, et **isolation tenant réelle
 * au niveau DB** (RLS `project_margin`) sous rôle non-superutilisateur — la policy rejette toute
 * écriture ou lecture cross-tenant sans le contexte `app.current_tenant`.
 */
final class DoctrineProjectMarginRepositoryTest extends KernelTestCase
{
    private const string PROJECT_A = '018f9c4e-0000-7000-8000-00000000aaaa';
    private const string PROJECT_B = '018f9c4e-0000-7000-8000-00000000bbbb';

    private EntityManagerInterface $em;
    private Connection $connection;
    private DoctrineProjectMarginRepository $repository;

    /** @var list<\Doctrine\ORM\Mapping\ClassMetadata<object>> */
    private array $schema;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->connection = $this->em->getConnection();
        $this->repository = new DoctrineProjectMarginRepository($this->em);

        $this->schema = [$this->em->getClassMetadata(ProjectMargin::class)];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        $this->connection->executeStatement('RESET app.current_tenant');
        $this->connection->executeStatement('ALTER TABLE project_margin ENABLE ROW LEVEL SECURITY');
        $this->connection->executeStatement('ALTER TABLE project_margin FORCE ROW LEVEL SECURITY');
        $this->connection->executeStatement('DROP POLICY IF EXISTS tenant_isolation ON project_margin');
        $this->connection->executeStatement(
            "CREATE POLICY tenant_isolation ON project_margin USING (tenant_id::text = current_setting('app.current_tenant', true))",
        );
    }

    protected function tearDown(): void
    {
        $this->connection->executeStatement('RESET ROLE');
        $this->connection->executeStatement('RESET app.current_tenant');
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->connection->executeStatement('DROP ROLE IF EXISTS margin_rls_probe');
        $this->em->close();
        parent::tearDown();
    }

    public function testReplaceForPeriodPersistsSortedByRevenueDescending(): void
    {
        $tenant = TenantId::generate();

        $this->repository->replaceForPeriod($tenant, '2026-11', [
            $this->margin($tenant, '2026-11', self::PROJECT_B, 'Appli mobile', 6_000_00, 6_500_00),
            $this->margin($tenant, '2026-11', self::PROJECT_A, 'Site vitrine', 10_000_00, 5_800_00),
        ]);

        $frozen = $this->repository->findForPeriod($tenant, '2026-11');
        self::assertCount(2, $frozen);
        self::assertSame(self::PROJECT_A, $frozen[0]->projectRef());
        self::assertSame(4_200_00, $frozen[0]->marginCents());
        self::assertSame(self::PROJECT_B, $frozen[1]->projectRef());
    }

    public function testReFreezingReplacesOnlyTargetPeriod(): void
    {
        $tenant = TenantId::generate();
        $this->repository->replaceForPeriod($tenant, '2026-10', [
            $this->margin($tenant, '2026-10', self::PROJECT_A, 'Site vitrine', 9_000_00, 4_000_00),
        ]);
        $this->repository->replaceForPeriod($tenant, '2026-11', [
            $this->margin($tenant, '2026-11', self::PROJECT_A, 'Site vitrine', 10_000_00, 5_800_00),
        ]);

        // Re-figeage de novembre : octobre inchangé (INV-2), novembre remplacé sans doublon.
        $this->repository->replaceForPeriod($tenant, '2026-11', [
            $this->margin($tenant, '2026-11', self::PROJECT_A, 'Site vitrine', 10_000_00, 4_000_00),
        ]);

        $october = $this->repository->findForPeriod($tenant, '2026-10');
        self::assertCount(1, $october);
        self::assertSame(5_000_00, $october[0]->marginCents());

        $november = $this->repository->findForPeriod($tenant, '2026-11');
        self::assertCount(1, $november);
        self::assertSame(6_000_00, $november[0]->marginCents());
    }

    public function testRlsRejectsCrossTenantWriteAndHidesCrossTenantRows(): void
    {
        $tenantA = TenantId::generate();
        $tenantB = TenantId::generate();

        $this->connection->executeStatement('DROP ROLE IF EXISTS margin_rls_probe');
        $this->connection->executeStatement('CREATE ROLE margin_rls_probe NOSUPERUSER');
        $this->connection->executeStatement('GRANT SELECT, INSERT ON project_margin TO margin_rls_probe');

        // Contexte tenant A posé sur la session, comme le middleware worker le ferait.
        $this->connection->executeStatement("SET app.current_tenant = '".$tenantA->toString()."'");
        $this->connection->executeStatement('SET ROLE margin_rls_probe');

        // Écriture pour le tenant du contexte : acceptée.
        $this->insertRaw($tenantA, self::PROJECT_A);

        // Écriture cross-tenant (tenant B sous contexte A) : rejetée par la barrière WITH CHECK.
        $rejected = false;
        try {
            $this->insertRaw($tenantB, self::PROJECT_B);
        } catch (\Doctrine\DBAL\Exception) {
            $rejected = true;
        }

        // Lecture sous contexte A : seules les lignes de A sont visibles.
        $visible = $this->connection->fetchOne('SELECT COUNT(*) FROM project_margin');

        $this->connection->executeStatement('RESET ROLE');

        self::assertTrue($rejected, 'La RLS doit rejeter une écriture cross-tenant.');
        self::assertSame(1, is_numeric($visible) ? (int) $visible : -1);
    }

    private function insertRaw(TenantId $tenant, string $projectRef): void
    {
        $this->connection->insert('project_margin', [
            'id' => TenantId::generate()->toString(),
            'tenant_id' => $tenant->toString(),
            'period' => '2026-11',
            'project_ref' => $projectRef,
            'project_name' => 'Projet',
            'revenue_cents' => 10_000_00,
            'cost_cents' => 5_800_00,
            'valued_count' => 12,
            'unvalued_count' => 0,
            'partial' => false,
            'frozen_at' => new DateTimeImmutable('2026-12-01 09:00:00', new DateTimeZone('UTC')),
        ], ['partial' => 'boolean', 'frozen_at' => 'datetime_immutable']);
    }

    private function margin(TenantId $tenant, string $period, string $projectRef, string $name, int $revenue, int $cost): ProjectMargin
    {
        return ProjectMargin::freeze(
            $tenant,
            $period,
            $projectRef,
            $name,
            $revenue,
            $cost,
            12,
            0,
            new DateTimeImmutable('2026-12-01 09:00:00', new DateTimeZone('UTC')),
        );
    }
}
