<?php

declare(strict_types=1);

namespace App\Tests\Integration\Analytics;

use App\Domain\Analytics\DimPeriod;
use App\Domain\Analytics\FactProjectRevenue;
use App\Domain\Analytics\RevenueRecognized;
use App\Domain\Analytics\StoredEvent;
use App\Domain\Tenant\TenantId;
use App\Infrastructure\Analytics\DoctrineAnalyticsProjector;
use App\Infrastructure\Analytics\SqlDivergenceChecker;
use App\Infrastructure\Persistence\Doctrine\DoctrineEventStore;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use DateTimeImmutable;

/**
 * US-005 / CA-2 — invariant de non-divergence : après reconstruction, le modèle est
 * fidèle à la source (0 écart) ; un événement non rejoué produit un écart détecté et
 * détaillé (ce test échoue en CI et bloque le build en cas de divergence — ARC-119).
 */
final class NonDivergenceTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private DoctrineEventStore $store;
    private DoctrineAnalyticsProjector $projector;
    private SqlDivergenceChecker $checker;

    /** @var list<\Doctrine\ORM\Mapping\ClassMetadata<object>> */
    private array $schema;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->store = new DoctrineEventStore($this->em);
        $this->projector = new DoctrineAnalyticsProjector($this->em, $this->store);
        $this->checker = new SqlDivergenceChecker($this->em, $this->store);

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

    public function testModelIsFaithfulToSourceAfterRebuild(): void
    {
        $tenant = TenantId::generate();
        $this->store->append(new RevenueRecognized($tenant, '2026-08', 'PRJ-1', 120000, new DateTimeImmutable()));
        $this->store->append(new RevenueRecognized($tenant, '2026-08', 'PRJ-2', 80000, new DateTimeImmutable()));

        $this->projector->rebuild($tenant);
        $this->em->clear();

        self::assertSame([], $this->checker->check($tenant), 'Aucune divergence attendue après reconstruction.');
    }

    public function testUnreplayedEventIsDetectedAsDivergence(): void
    {
        $tenant = TenantId::generate();
        $this->store->append(new RevenueRecognized($tenant, '2026-08', 'PRJ-1', 120000, new DateTimeImmutable()));
        $this->projector->rebuild($tenant);

        // Un événement survient mais n'est pas rejoué (bug de consommateur — CA-3) :
        // la source diverge du modèle.
        $this->store->append(new RevenueRecognized($tenant, '2026-08', 'PRJ-1', 5000, new DateTimeImmutable()));
        $this->em->clear();

        $divergences = $this->checker->check($tenant);

        self::assertCount(1, $divergences);
        $divergence = $divergences[0];
        self::assertSame('project_revenue', $divergence->indicator);
        self::assertSame('2026-08', $divergence->period);
        self::assertSame(125000, $divergence->expectedCents, 'Source = 120000 + 5000.');
        self::assertSame(120000, $divergence->actualCents, 'Modèle = 120000 (5000 non rejoué).');
        self::assertSame(-5000, $divergence->deltaCents());
    }
}
