<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Domain\Absence\AbsenceRequest;
use App\Domain\Period\AccountingPeriod;
use App\Domain\Period\ReopeningRequest;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectAssignment;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use App\Domain\Timesheet\TimeEntry;
use App\Domain\User\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\SodiumPasswordHasher;
use DateTimeImmutable;
use DateTimeZone;

/**
 * US-057 (T-057-08, CA-4) — toute saisie sur une période clôturée est refusée par l'API avec
 * **423 Locked**, tandis qu'une période ouverte accepte la saisie (201).
 */
final class PeriodLockApiTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
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
            $this->em->getClassMetadata(ReopeningRequest::class),
            $this->em->getClassMetadata(AbsenceRequest::class),
        ];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        $tenant = TenantId::generate();
        $this->em->persist(new Tenant($tenant, 'Agence A'));
        $this->em->persist(new User($tenant, 'camille@agence.test', new SodiumPasswordHasher()->hash('motdepasse-solide')));
        $project = new Project($tenant, 'PRJ-1', 'Refonte');
        $this->em->persist($project);
        $this->projectId = $project->id();

        // Août 2026 clôturée ; septembre reste ouvert.
        $closed = new AccountingPeriod($tenant, '2026-08');
        $closed->close(TenantId::generate()->toString(), new DateTimeImmutable('2026-09-01 10:00:00', new DateTimeZone('UTC')));
        $this->em->persist($closed);
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->em->close();
        parent::tearDown();
    }

    public function testRecordingOnAClosedPeriodIsLocked(): void
    {
        $this->login();

        $this->post(['projectId' => $this->projectId, 'date' => '2026-08-15', 'minutes' => 240]);

        self::assertResponseStatusCodeSame(423);
        /** @var array{error?: string} $body */
        $body = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertStringContainsString('clôturée', $body['error'] ?? '');
    }

    public function testRecordingOnAnOpenPeriodSucceeds(): void
    {
        $this->login();

        $this->post(['projectId' => $this->projectId, 'date' => '2026-09-15', 'minutes' => 240]);

        self::assertResponseStatusCodeSame(201);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function post(array $payload): void
    {
        $this->client->request(
            'POST',
            '/api/time-entries',
            server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            content: json_encode($payload, JSON_THROW_ON_ERROR),
        );
    }

    private function login(): void
    {
        $this->client->request(
            'POST',
            '/api/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => 'camille@agence.test', 'password' => 'motdepasse-solide'], JSON_THROW_ON_ERROR),
        );
        self::assertResponseIsSuccessful();
    }
}
