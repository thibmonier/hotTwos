<?php

declare(strict_types=1);

namespace App\Tests\Integration\Valuation;

use App\Domain\Project\Project;
use App\Domain\Tenant\TenantId;
use App\Domain\Timesheet\TimeEntry;
use App\Domain\Valuation\TimeEntryValuation;
use App\Infrastructure\Persistence\Doctrine\DoctrineTimeEntryValuationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use DateTimeImmutable;
use DateTimeZone;

/**
 * US-032 (CA-2, RG-PRJ-6) — la ventilation par projet pour la marge exclut les projets internes non
 * facturables (filtre DQL `p.internal = false`).
 */
final class ProjectBreakdownInternalExclusionTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private DoctrineTimeEntryValuationRepository $repository;

    /** @var list<\Doctrine\ORM\Mapping\ClassMetadata<object>> */
    private array $schema;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->repository = new DoctrineTimeEntryValuationRepository($this->em);

        $this->schema = [
            $this->em->getClassMetadata(Project::class),
            $this->em->getClassMetadata(TimeEntry::class),
            $this->em->getClassMetadata(TimeEntryValuation::class),
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

    public function testInternalProjectsAreExcludedFromBreakdown(): void
    {
        $tenant = TenantId::generate();

        $billable = new Project($tenant, 'VIT', 'Site vitrine');
        $this->em->persist($billable);

        $internal = new Project($tenant, 'RND', 'R&D interne');
        $internal->markInternal(true);
        $this->em->persist($internal);

        $when = new DateTimeImmutable('2026-08-20 10:00:00', new DateTimeZone('UTC'));
        $rateDate = new DateTimeImmutable('2026-01-01 00:00:00', new DateTimeZone('UTC'));
        foreach ([$billable, $internal] as $project) {
            $entry = new TimeEntry($tenant, '018f9c4e-0000-7000-8000-0000000000c1', $project->id(), new DateTimeImmutable('2026-08-03'), 420);
            $this->em->persist($entry);
            $this->em->persist(TimeEntryValuation::valued($tenant, $entry->id(), 45000, 78000, 45000, 78000, $rateDate, $when));
        }
        $this->em->flush();

        $breakdown = $this->repository->projectBreakdownFor($tenant);

        self::assertCount(1, $breakdown, 'Le projet interne est exclu de la ventilation de marge.');
        self::assertSame($billable->id(), $breakdown[0]->projectId);
    }
}
