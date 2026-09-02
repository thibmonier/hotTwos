<?php

declare(strict_types=1);

namespace App\Tests\Functional\Timesheet;

use App\Domain\Project\Project;
use App\Domain\Project\ProjectAssignment;
use App\Domain\Reminder\ReminderPreference;
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

/**
 * US-051 — enregistrement d'une semaine complète en une requête, et présence de la ligne
 * « Absence » dans la grille.
 */
final class WeekBatchTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private User $user;
    private string $projectId;

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
            $this->em->getClassMetadata(ReminderPreference::class),
        ];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        $tenant = TenantId::generate();
        $this->user = new User($tenant, 'camille@agence.test', new SodiumPasswordHasher()->hash('x'));
        $project = new Project($tenant, 'PRJ-1', 'Refonte');
        $this->em->persist(new Tenant($tenant, 'Agence A'));
        $this->em->persist($this->user);
        $this->em->persist($project);
        $this->em->flush();
        $this->projectId = $project->id();
    }

    protected function tearDown(): void
    {
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->em->close();
        parent::tearDown();
    }

    public function testRecordsAFullWeekInOneRequest(): void
    {
        $this->client->loginUser($this->user);

        $entries = [];
        foreach (['2026-09-14', '2026-09-15', '2026-09-16', '2026-09-17', '2026-09-18'] as $day) {
            $entries[] = ['projectId' => $this->projectId, 'date' => $day, 'minutes' => 420];
        }
        $this->client->request('POST', '/api/time-entries/week', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['entries' => $entries], \JSON_THROW_ON_ERROR));

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        self::assertSame(5, $payload['recorded'] ?? null);

        $count = (int) $this->em->createQuery('SELECT COUNT(e.id) FROM '.TimeEntry::class.' e')->getSingleScalarResult();
        self::assertSame(5, $count);
    }

    public function testWeekPageIncludesAbsenceRow(): void
    {
        $this->client->loginUser($this->user);
        $this->client->request('GET', '/saisie');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Absence', (string) $this->client->getResponse()->getContent());
    }
}
