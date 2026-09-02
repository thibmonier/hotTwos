<?php

declare(strict_types=1);

namespace App\Tests\Functional\Timesheet;

use App\Domain\Project\Project;
use App\Domain\Project\ProjectAssignment;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use App\Domain\Absence\AbsenceRequest;
use App\Domain\Period\AccountingPeriod;
use App\Domain\Timesheet\TimeEntry;
use App\Domain\User\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\SodiumPasswordHasher;
use DateTimeImmutable;

/**
 * US-051 — duplication de la semaine précédente et enregistrement d'une semaine
 * représentative (5 projets × 5 jours) en une seule requête (levier ≤ 2 min).
 */
final class DuplicateWeekFlowTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private User $user;
    private TenantId $tenant;
    /** @var list<string> */
    private array $projectIds = [];

    /** @var list<\Doctrine\ORM\Mapping\ClassMetadata<object>> */
    private array $schema;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        $this->schema = [
            $this->em->getClassMetadata(Tenant::class),
            $this->em->getClassMetadata(User::class),
            $this->em->getClassMetadata(Project::class),
            $this->em->getClassMetadata(TimeEntry::class),
            $this->em->getClassMetadata(ProjectAssignment::class),
            $this->em->getClassMetadata(AccountingPeriod::class),
            $this->em->getClassMetadata(AbsenceRequest::class),
        ];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        $this->tenant = TenantId::generate();
        $this->user = new User($this->tenant, 'camille@agence.test', new SodiumPasswordHasher()->hash('x'));
        $this->em->persist(new Tenant($this->tenant, 'Agence A'));
        $this->em->persist($this->user);
        for ($i = 1; $i <= 5; ++$i) {
            $project = new Project($this->tenant, 'PRJ-'.$i, 'Projet '.$i);
            $this->em->persist($project);
            $this->projectIds[] = $project->id();
        }
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->em->close();
        parent::tearDown();
    }

    public function testRepresentativeWeekIsRecordedInOneRequest(): void
    {
        $this->client->loginUser($this->user);

        $entries = [];
        foreach (['2026-09-14', '2026-09-15', '2026-09-16', '2026-09-17', '2026-09-18'] as $day) {
            foreach ($this->projectIds as $projectId) {
                $entries[] = ['projectId' => $projectId, 'date' => $day, 'minutes' => 96]; // 5 projets ≈ journée
            }
        }
        $this->post('/api/time-entries/week', ['entries' => $entries]);

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        self::assertSame(25, $payload['recorded'] ?? null, '5 projets × 5 jours en une requête.');
    }

    public function testDuplicatesPreviousWeekIntoTarget(): void
    {
        // Semaine précédente (lundi 2026-09-07) déjà saisie sur 2 projets.
        $this->em->persist(new TimeEntry($this->tenant, $this->user->id(), $this->projectIds[0], new DateTimeImmutable('2026-09-07'), 420));
        $this->em->persist(new TimeEntry($this->tenant, $this->user->id(), $this->projectIds[1], new DateTimeImmutable('2026-09-08'), 300));
        $this->em->flush();

        $this->client->loginUser($this->user);
        $this->post('/api/time-entries/duplicate-week', ['weekStart' => '2026-09-14']);

        self::assertResponseIsSuccessful();
        $inTarget = (int) $this->em->createQuery(
            'SELECT COUNT(e.id) FROM '.TimeEntry::class.' e WHERE e.workDate BETWEEN :from AND :to',
        )->setParameter('from', new DateTimeImmutable('2026-09-14'), 'date_immutable')
            ->setParameter('to', new DateTimeImmutable('2026-09-20'), 'date_immutable')
            ->getSingleScalarResult();
        self::assertSame(2, $inTarget, 'Les 2 imputations sont reportées dans la semaine cible.');
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function post(string $path, array $payload): void
    {
        $this->client->request('POST', $path, server: ['CONTENT_TYPE' => 'application/json'], content: json_encode($payload, \JSON_THROW_ON_ERROR));
    }
}
