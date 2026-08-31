<?php

declare(strict_types=1);

namespace App\Tests\Integration\Analytics;

use App\Domain\Analytics\DimPeriod;
use App\Domain\Analytics\FactProjectRevenue;
use App\Domain\Analytics\RevenueRecognized;
use App\Domain\Analytics\StoredEvent;
use App\Domain\Tenant\TenantId;
use App\Infrastructure\Analytics\DoctrineAnalyticsProjector;
use App\Infrastructure\Persistence\Doctrine\DoctrineEventStore;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use DateTimeImmutable;

/**
 * US-005 — la reconstruction du modèle en étoile est déterministe (CA-1, idempotente),
 * bornée au tenant, et cohérente sur un tenant sans événements (CA-5).
 */
final class AnalyticsProjectorTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private DoctrineEventStore $store;
    private DoctrineAnalyticsProjector $projector;

    /** @var list<\Doctrine\ORM\Mapping\ClassMetadata<object>> */
    private array $schema;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->store = new DoctrineEventStore($this->em);
        $this->projector = new DoctrineAnalyticsProjector($this->em, $this->store);

        $this->schema = [
            $this->em->getClassMetadata(StoredEvent::class),
            $this->em->getClassMetadata(DimPeriod::class),
            $this->em->getClassMetadata(FactProjectRevenue::class),
        ];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);
    }

    protected function tearDown(): void
    {
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->em->close();
        parent::tearDown();
    }

    public function testRebuildAggregatesRevenueAndIsIdempotent(): void
    {
        $tenant = TenantId::generate();
        $this->store->append(new RevenueRecognized($tenant, '2026-08', 'PRJ-1', 120000, new DateTimeImmutable()));
        $this->store->append(new RevenueRecognized($tenant, '2026-08', 'PRJ-1', 30000, new DateTimeImmutable()));
        $this->store->append(new RevenueRecognized($tenant, '2026-08', 'PRJ-2', 50000, new DateTimeImmutable()));

        $this->projector->rebuild($tenant);
        $this->em->clear();
        $firstTotal = $this->revenueFor($tenant, '2026-08');
        $firstRows = $this->factCountFor($tenant);

        // La reconstruction rejoue le même flux : agrégats strictement identiques (CA-1).
        $this->projector->rebuild($tenant);
        $this->em->clear();

        self::assertSame(200000, $firstTotal, 'Somme attendue : 120000 + 30000 + 50000.');
        self::assertSame(200000, $this->revenueFor($tenant, '2026-08'));
        self::assertSame(2, $firstRows, 'Grain (période, projet) : 2 lignes de faits.');
        self::assertSame(2, $this->factCountFor($tenant), 'Pas de doublon après ré-application (idempotence).');
    }

    public function testRebuildIsScopedToTenant(): void
    {
        $tenantA = TenantId::generate();
        $tenantB = TenantId::generate();
        $this->store->append(new RevenueRecognized($tenantA, '2026-08', 'PRJ-1', 100000, new DateTimeImmutable()));
        $this->store->append(new RevenueRecognized($tenantB, '2026-08', 'PRJ-1', 999000, new DateTimeImmutable()));

        $this->projector->rebuild($tenantA);
        $this->em->clear();

        // La reconstruction de A ne matérialise que les faits de A.
        self::assertSame(100000, $this->revenueFor($tenantA, '2026-08'));
        self::assertSame(0, $this->factCountFor($tenantB), 'Le tenant B n\'est pas touché par la reconstruction de A.');
    }

    public function testRebuildOnTenantWithoutEventsProducesEmptyModel(): void
    {
        $tenant = TenantId::generate();

        $this->projector->rebuild($tenant);
        $this->em->clear();

        self::assertSame(0, $this->factCountFor($tenant));
        self::assertSame(0, $this->dimCountFor($tenant));
    }

    private function revenueFor(TenantId $tenant, string $period): int
    {
        $sum = $this->em->createQuery(
            'SELECT SUM(f.amountCents) FROM '.FactProjectRevenue::class.' f WHERE f.tenantId = :tenant AND f.period = :period',
        )->setParameter('tenant', $tenant->toString())->setParameter('period', $period)->getSingleScalarResult();

        return null === $sum ? 0 : (int) $sum;
    }

    private function factCountFor(TenantId $tenant): int
    {
        return (int) $this->em->createQuery(
            'SELECT COUNT(f.id) FROM '.FactProjectRevenue::class.' f WHERE f.tenantId = :tenant',
        )->setParameter('tenant', $tenant->toString())->getSingleScalarResult();
    }

    private function dimCountFor(TenantId $tenant): int
    {
        return (int) $this->em->createQuery(
            'SELECT COUNT(d.id) FROM '.DimPeriod::class.' d WHERE d.tenantId = :tenant',
        )->setParameter('tenant', $tenant->toString())->getSingleScalarResult();
    }
}
