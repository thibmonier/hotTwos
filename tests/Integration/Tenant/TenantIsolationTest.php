<?php

declare(strict_types=1);

namespace App\Tests\Integration\Tenant;

use App\Domain\Sample\ProtectedRecord;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use App\Infrastructure\Persistence\Doctrine\Filter\TenantFilter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * US-001 / ENF-SEC-4 (critère bloquant) — l'isolation multi-tenant est effective :
 * une requête filtrée par le tenant A ne voit jamais les données du tenant B (ARC-33).
 */
final class TenantIsolationTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    /** @var list<\Doctrine\ORM\Mapping\ClassMetadata<object>> */
    private array $schema;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        $this->schema = [
            $this->em->getClassMetadata(Tenant::class),
            $this->em->getClassMetadata(ProtectedRecord::class),
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

    public function testFilterIsolatesRecordsByTenant(): void
    {
        $tenantA = TenantId::generate();
        $tenantB = TenantId::generate();

        $this->em->persist(new Tenant($tenantA, 'Agence A'));
        $this->em->persist(new Tenant($tenantB, 'Agence B'));
        $this->em->persist(new ProtectedRecord($tenantA, 'secret-A'));
        $this->em->persist(new ProtectedRecord($tenantB, 'secret-B'));
        $this->em->flush();
        $this->em->clear();

        $repository = $this->em->getRepository(ProtectedRecord::class);

        // Sans filtre, les deux enregistrements coexistent bien en base.
        self::assertCount(2, $repository->findAll());

        // Filtre positionné sur le tenant A : seules ses données sont visibles.
        $filter = $this->em->getFilters()->enable(TenantFilter::NAME);
        $filter->setParameter(TenantFilter::PARAMETER, $tenantA->toString());
        $this->em->clear();

        $visibleForA = $repository->findAll();
        self::assertCount(1, $visibleForA);
        self::assertSame('secret-A', $visibleForA[0]->label());

        // Bascule sur le tenant B : les données de A deviennent invisibles.
        $filter->setParameter(TenantFilter::PARAMETER, $tenantB->toString());
        $this->em->clear();

        $visibleForB = $repository->findAll();
        self::assertCount(1, $visibleForB);
        self::assertSame('secret-B', $visibleForB[0]->label());
    }
}
