<?php

declare(strict_types=1);

namespace App\Tests\Integration\Organization;

use App\Domain\Organization\OrgMembership;
use App\Domain\Shared\EffectivePeriod;
use App\Domain\Tenant\TenantId;
use App\Infrastructure\Persistence\Doctrine\DoctrineOrgMembershipRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use DateTimeZone;

/**
 * US-010 (T-010-03) — le repository persiste les rattachements et les restitue par collaborateur
 * et par unité, strictement cloisonnés au tenant demandé.
 */
final class DoctrineOrgMembershipRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private DoctrineOrgMembershipRepository $repository;

    /** @var list<\Doctrine\ORM\Mapping\ClassMetadata<object>> */
    private array $schema;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->repository = new DoctrineOrgMembershipRepository($this->em);

        $this->schema = [$this->em->getClassMetadata(OrgMembership::class)];
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

    public function testSaveThenFindForUserReturnsMembershipsNewestFirst(): void
    {
        $tenant = TenantId::generate();
        $user = TenantId::generate()->toString();
        $unitA = TenantId::generate()->toString();
        $unitB = TenantId::generate()->toString();

        $old = new OrgMembership($tenant, $user, $unitA, EffectivePeriod::between($this->date('2025-01-01'), $this->date('2026-01-01')));
        $current = new OrgMembership($tenant, $user, $unitB, EffectivePeriod::since($this->date('2026-01-01')));
        $this->repository->save($old);
        $this->repository->save($current);
        $this->em->clear();

        $history = $this->repository->findForUser($tenant, $user);

        self::assertCount(2, $history);
        // Ordre décroissant par date d'effet : le rattachement en cours d'abord.
        self::assertSame($unitB, $history[0]->orgUnitId());
        self::assertSame($unitA, $history[1]->orgUnitId());
    }

    public function testFindForUserIsScopedToTenant(): void
    {
        $tenantA = TenantId::generate();
        $tenantB = TenantId::generate();
        $user = TenantId::generate()->toString();
        $unit = TenantId::generate()->toString();

        $this->repository->save(new OrgMembership($tenantA, $user, $unit, EffectivePeriod::since($this->date('2026-01-01'))));
        $this->repository->save(new OrgMembership($tenantB, $user, $unit, EffectivePeriod::since($this->date('2026-01-01'))));
        $this->em->clear();

        self::assertCount(1, $this->repository->findForUser($tenantA, $user));
        self::assertCount(1, $this->repository->findForUser($tenantB, $user));
    }

    public function testFindForOrgUnitReturnsReferencingMemberships(): void
    {
        $tenant = TenantId::generate();
        $unit = TenantId::generate()->toString();
        $otherUnit = TenantId::generate()->toString();

        $this->repository->save(new OrgMembership($tenant, TenantId::generate()->toString(), $unit, EffectivePeriod::since($this->date('2026-01-01'))));
        $this->repository->save(new OrgMembership($tenant, TenantId::generate()->toString(), $otherUnit, EffectivePeriod::since($this->date('2026-01-01'))));
        $this->em->clear();

        $referencing = $this->repository->findForOrgUnit($tenant, $unit);

        self::assertCount(1, $referencing);
        self::assertSame($unit, $referencing[0]->orgUnitId());
    }

    private function date(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value.' 00:00:00', new DateTimeZone('UTC'));
    }
}
