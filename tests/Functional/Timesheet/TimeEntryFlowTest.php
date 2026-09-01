<?php

declare(strict_types=1);

namespace App\Tests\Functional\Timesheet;

use App\Domain\Project\Project;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use App\Domain\Period\AccountingPeriod;
use App\Domain\Timesheet\TimeEntry;
use App\Domain\User\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\SodiumPasswordHasher;

/**
 * US-050 — un collaborateur authentifié saisit une imputation sur un projet actif de son
 * tenant ; une durée invalide est refusée (422) ; sans authentification, 401.
 */
final class TimeEntryFlowTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private TenantId $tenant;
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
            $this->em->getClassMetadata(AccountingPeriod::class),
        ];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        $this->tenant = TenantId::generate();
        $hasher = new SodiumPasswordHasher();
        $this->em->persist(new Tenant($this->tenant, 'Agence A'));
        $this->em->persist(new User($this->tenant, 'camille@agence.test', $hasher->hash('motdepasse-solide')));
        $project = new Project($this->tenant, 'PRJ-1', 'Refonte SI');
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

    public function testAuthenticatedUserRecordsTimeEntry(): void
    {
        $this->login();

        $this->post(['projectId' => $this->projectId, 'date' => '2026-09-15', 'minutes' => 240, 'comment' => 'matinée']);

        self::assertResponseStatusCodeSame(201);

        $count = (int) $this->em->createQuery('SELECT COUNT(e.id) FROM '.TimeEntry::class.' e')->getSingleScalarResult();
        self::assertSame(1, $count);
    }

    public function testInvalidDurationIsRejected(): void
    {
        $this->login();

        $this->post(['projectId' => $this->projectId, 'date' => '2026-09-15', 'minutes' => 0]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testUnknownProjectIsRejected(): void
    {
        $this->login();

        $this->post(['projectId' => TenantId::generate()->toString(), 'date' => '2026-09-15', 'minutes' => 60]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testRequiresAuthentication(): void
    {
        $this->post(['projectId' => $this->projectId, 'date' => '2026-09-15', 'minutes' => 60]);

        self::assertResponseStatusCodeSame(401);
    }

    private function login(): void
    {
        $this->client->request(
            'POST',
            '/api/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => 'camille@agence.test', 'password' => 'motdepasse-solide'], \JSON_THROW_ON_ERROR),
        );
        self::assertResponseIsSuccessful();
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function post(array $payload): void
    {
        $this->client->request(
            'POST',
            '/api/time-entries',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload, \JSON_THROW_ON_ERROR),
        );
    }
}
