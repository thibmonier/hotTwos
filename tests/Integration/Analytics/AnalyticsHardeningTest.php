<?php

declare(strict_types=1);

namespace App\Tests\Integration\Analytics;

use App\Domain\Analytics\DimPeriod;
use App\Domain\Analytics\FactProjectRevenue;
use App\Domain\Analytics\RevenueRecognized;
use App\Domain\Analytics\StoredEvent;
use App\Domain\Tenant\TenantId;
use App\Infrastructure\Analytics\AnalyticsSchemaHardener;
use App\Infrastructure\Analytics\DoctrineAnalyticsProjector;
use App\Infrastructure\Persistence\Doctrine\DoctrineEventStore;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use DateTimeImmutable;
use Throwable;

/**
 * US-005 — double barrière d'isolation (CA-4) et protection anti-écriture directe (CA-6)
 * sur les tables analytiques.
 */
final class AnalyticsHardeningTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private Connection $connection;
    private DoctrineAnalyticsProjector $projector;

    /** @var list<\Doctrine\ORM\Mapping\ClassMetadata<object>> */
    private array $schema;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->connection = $this->em->getConnection();
        $this->projector = new DoctrineAnalyticsProjector($this->em, new DoctrineEventStore($this->em));

        $this->schema = [
            $this->em->getClassMetadata(StoredEvent::class),
            $this->em->getClassMetadata(DimPeriod::class),
            $this->em->getClassMetadata(FactProjectRevenue::class),
        ];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        new AnalyticsSchemaHardener($this->connection)->harden();
    }

    protected function tearDown(): void
    {
        $this->connection->executeStatement('RESET ROLE');
        $this->connection->executeStatement('RESET app.current_tenant');
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->connection->executeStatement('DROP ROLE IF EXISTS rls_probe');
        $this->em->close();
        parent::tearDown();
    }

    public function testDirectWriteIntoFactTableIsRejected(): void
    {
        $tenant = TenantId::generate();

        $message = null;
        try {
            $this->connection->executeStatement(sprintf(
                'INSERT INTO fact_project_revenue (id, tenant_id, period, project_ref, amount_cents)'
                ." VALUES ('%s', '%s', '2026-08', 'PRJ-HACK', 999)",
                TenantId::generate()->toString(),
                $tenant->toString(),
            ));
        } catch (Throwable $exception) {
            $message = $exception->getMessage();
        }

        self::assertNotNull($message, 'L\'écriture directe aurait dû être rejetée.');
        self::assertStringContainsString('Écriture directe interdite dans les tables analytiques', $message);

        self::assertSame(0, $this->countFacts(), 'Aucune ligne ne doit avoir été insérée.');
    }

    public function testProjectorWriteThroughEventChannelIsAllowed(): void
    {
        $tenant = TenantId::generate();
        new DoctrineEventStore($this->em)->append(
            new RevenueRecognized($tenant, '2026-08', 'PRJ-1', 42000, new DateTimeImmutable()),
        );

        $this->projector->rebuild($tenant);

        self::assertSame(1, $this->countFacts(), 'Le projecteur (canal événementiel) écrit normalement.');
    }

    public function testRowLevelSecurityIsolatesTenantsWithoutOrmFilter(): void
    {
        $tenantA = TenantId::generate();
        $tenantB = TenantId::generate();
        $store = new DoctrineEventStore($this->em);
        $store->append(new RevenueRecognized($tenantA, '2026-08', 'PRJ-1', 100, new DateTimeImmutable()));
        $store->append(new RevenueRecognized($tenantA, '2026-08', 'PRJ-2', 200, new DateTimeImmutable()));
        $store->append(new RevenueRecognized($tenantB, '2026-08', 'PRJ-9', 900, new DateTimeImmutable()));
        $this->projector->rebuild($tenantA);
        $this->projector->rebuild($tenantB);

        // Rôle non-superutilisateur : la RLS s'applique réellement (le superutilisateur
        // la contournerait). Le filtre ORM n'est pas activé ici — la RLS seule opère.
        $this->connection->executeStatement('DROP ROLE IF EXISTS rls_probe');
        $this->connection->executeStatement('CREATE ROLE rls_probe NOSUPERUSER');
        $this->connection->executeStatement('GRANT SELECT ON fact_project_revenue TO rls_probe');

        $this->connection->executeStatement(sprintf("SET app.current_tenant = '%s'", $tenantA->toString()));
        $this->connection->executeStatement('SET ROLE rls_probe');
        $visibleForA = $this->countFacts();

        $this->connection->executeStatement('RESET app.current_tenant');
        $visibleWithoutContext = $this->countFacts();

        $this->connection->executeStatement('RESET ROLE');

        self::assertSame(2, $visibleForA, 'Seuls les 2 faits du tenant A sont visibles sous sa session.');
        self::assertSame(0, $visibleWithoutContext, 'Sans app.current_tenant, la RLS masque toutes les lignes.');
    }

    private function countFacts(): int
    {
        $value = $this->connection->fetchOne('SELECT COUNT(*) FROM fact_project_revenue');
        if (!is_numeric($value)) {
            self::fail('Le COUNT analytique n\'a pas renvoyé de valeur numérique.');
        }

        return (int) $value;
    }
}
