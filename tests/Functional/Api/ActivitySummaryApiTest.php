<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Domain\Project\Project;
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

/**
 * US-059 (T-059-02/05) — API synthèse : cloisonnement strict au collaborateur courant (403 sur un
 * autre user_id), état vide explicite (pas de 500), planning dégradé (US-037 absente), 401 anonyme.
 */
final class ActivitySummaryApiTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private User $camille;
    private User $marc;

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
        ];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        $tenant = TenantId::generate();
        $hasher = new SodiumPasswordHasher();
        $this->camille = new User($tenant, 'camille@agence.test', $hasher->hash('motdepasse-solide'));
        $this->marc = new User($tenant, 'marc@agence.test', $hasher->hash('motdepasse-solide'));
        $project = new Project($tenant, 'ALPHA', 'Projet Alpha');
        $this->em->persist(new Tenant($tenant, 'Agence A'));
        $this->em->persist($this->camille);
        $this->em->persist($this->marc);
        $this->em->persist($project);
        $this->em->persist(new TimeEntry($tenant, $this->camille->id(), $project->id(), new DateTimeImmutable('monday this week'), 420));
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->em->close();
        parent::tearDown();
    }

    public function testUnauthenticatedIsRejected(): void
    {
        $this->client->request('GET', '/api/activity-summary', server: ['HTTP_ACCEPT' => 'application/json']);

        self::assertResponseStatusCodeSame(401);
    }

    public function testSelfSummaryReturnsBreakdownAndDegradedPlanning(): void
    {
        $this->login('camille@agence.test');
        $this->client->request('GET', '/api/activity-summary', server: ['HTTP_ACCEPT' => 'application/json']);

        self::assertResponseIsSuccessful();
        $body = $this->decode();
        self::assertFalse($body['empty'] ?? true);
        self::assertSame(420, $body['totalMinutes'] ?? null);
        self::assertNotEmpty($body['byProject'] ?? []);
        $planning = $body['planning'] ?? [];
        self::assertIsArray($planning);
        self::assertFalse($planning['available'] ?? true);
    }

    public function testForeignUserIdIsForbidden(): void
    {
        $this->login('camille@agence.test');
        $this->client->request('GET', '/api/activity-summary?user_id='.$this->marc->id(), server: ['HTTP_ACCEPT' => 'application/json']);

        self::assertResponseStatusCodeSame(403);
    }

    public function testOwnUserIdIsAllowed(): void
    {
        $this->login('camille@agence.test');
        $this->client->request('GET', '/api/activity-summary?user_id='.$this->camille->id(), server: ['HTTP_ACCEPT' => 'application/json']);

        self::assertResponseIsSuccessful();
    }

    public function testEmptySummaryIsNotAnError(): void
    {
        $this->login('marc@agence.test'); // aucune imputation
        $this->client->request('GET', '/api/activity-summary', server: ['HTTP_ACCEPT' => 'application/json']);

        self::assertResponseIsSuccessful();
        self::assertTrue($this->decode()['empty'] ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(): array
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);

        return $decoded;
    }

    private function login(string $email): void
    {
        $this->client->request('POST', '/api/login', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['email' => $email, 'password' => 'motdepasse-solide'], JSON_THROW_ON_ERROR));
        self::assertResponseIsSuccessful();
    }
}
