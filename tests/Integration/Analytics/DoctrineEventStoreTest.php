<?php

declare(strict_types=1);

namespace App\Tests\Integration\Analytics;

use App\Domain\Analytics\RevenueRecognized;
use App\Domain\Analytics\StoredEvent;
use App\Domain\Tenant\TenantId;
use App\Infrastructure\Persistence\Doctrine\DoctrineEventStore;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use DateTimeImmutable;

/**
 * US-005 — le flux d'événements attribue une séquence par tenant, restitue le flux
 * ordonné et cloisonne les tenants (ADR-9).
 */
final class DoctrineEventStoreTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private DoctrineEventStore $store;

    /** @var list<\Doctrine\ORM\Mapping\ClassMetadata<object>> */
    private array $schema;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->store = new DoctrineEventStore($this->em);

        $this->schema = [$this->em->getClassMetadata(StoredEvent::class)];
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

    public function testAssignsPerTenantSequenceAndIsolatesStreams(): void
    {
        $tenantA = TenantId::generate();
        $tenantB = TenantId::generate();

        $this->store->append(new RevenueRecognized($tenantA, '2026-08', 'PRJ-1', 1000, new DateTimeImmutable()));
        $this->store->append(new RevenueRecognized($tenantA, '2026-08', 'PRJ-2', 2000, new DateTimeImmutable()));
        $this->store->append(new RevenueRecognized($tenantB, '2026-08', 'PRJ-9', 9000, new DateTimeImmutable()));

        $streamA = $this->store->streamFor($tenantA);
        self::assertCount(2, $streamA);
        self::assertSame([1, 2], array_map(static fn (StoredEvent $e): int => $e->sequence(), $streamA));

        $streamB = $this->store->streamFor($tenantB);
        self::assertCount(1, $streamB);
        self::assertSame(1, $streamB[0]->sequence(), 'La séquence est propre à chaque tenant.');
    }
}
