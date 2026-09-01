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
 * US-060 (T-060-07, ARC-113 / INV-2) — non-divergence de l'indicateur **valorisé** : le CA réel
 * issu de la valorisation (événements `RevenueRecognized` porteurs d'une imputation source) est
 * projeté sans écart, et une re-valorisation **remplace** la reconnaissance précédente au lieu de
 * la cumuler (pas de double comptage), le modèle restant fidèle à la source.
 */
final class ValuedRevenueNonDivergenceTest extends KernelTestCase
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

    public function testRevaluationSupersedesWithoutDivergence(): void
    {
        $tenant = TenantId::generate();

        // Valorisation initiale de deux imputations.
        $this->store->append(new RevenueRecognized($tenant, '2026-08', 'PRJ-1', 78000, new DateTimeImmutable(), 'entry-1'));
        $this->store->append(new RevenueRecognized($tenant, '2026-08', 'PRJ-1', 30000, new DateTimeImmutable(), 'entry-2'));
        // Re-valorisation de la première imputation (révision/recalcul) : remplace, ne cumule pas.
        $this->store->append(new RevenueRecognized($tenant, '2026-08', 'PRJ-1', 90000, new DateTimeImmutable(), 'entry-1'));

        $this->projector->rebuild($tenant);
        $this->em->clear();

        // Dernière reconnaissance par imputation : 90000 (entry-1) + 30000 (entry-2) = 120000.
        self::assertSame(120000, $this->revenueFor($tenant, '2026-08'), 'Le re-calcul remplace la valorisation précédente (pas de double comptage).');
        self::assertSame([], $this->checker->check($tenant), 'Le modèle valorisé reste fidèle à la source (0 écart).');
    }

    public function testValuedAndProbeRevenueCoexistWithoutDivergence(): void
    {
        $tenant = TenantId::generate();

        // Un CA valorisé (avec imputation source) et une reconnaissance sonde (sans source) cohabitent.
        $this->store->append(new RevenueRecognized($tenant, '2026-08', 'PRJ-1', 78000, new DateTimeImmutable(), 'entry-1'));
        $this->store->append(new RevenueRecognized($tenant, '2026-08', 'PRJ-1', 12000, new DateTimeImmutable()));

        $this->projector->rebuild($tenant);
        $this->em->clear();

        self::assertSame(90000, $this->revenueFor($tenant, '2026-08'));
        self::assertSame([], $this->checker->check($tenant));
    }

    private function revenueFor(TenantId $tenant, string $period): int
    {
        $sum = $this->em->createQuery(
            'SELECT SUM(f.amountCents) FROM '.FactProjectRevenue::class.' f WHERE f.tenantId = :tenant AND f.period = :period',
        )->setParameter('tenant', $tenant->toString())->setParameter('period', $period)->getSingleScalarResult();

        return null === $sum ? 0 : (int) $sum;
    }
}
