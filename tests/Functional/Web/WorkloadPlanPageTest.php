<?php

declare(strict_types=1);

namespace App\Tests\Functional\Web;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Project\ProjectAssignment;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Infrastructure\Persistence\Doctrine\DoctrineRoleRepository;
use App\Tests\Support\Schema\ProvisionsFullSchema;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\SodiumPasswordHasher;

/**
 * US-041 (EPIC-004) — la page /planification/charge affiche capacité vs charge ferme ; gating.
 */
final class WorkloadPlanPageTest extends WebTestCase
{
    use ProvisionsFullSchema;

    private const string PASSWORD = 'motdepasse-solide';

    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private TenantId $tenant;
    private string $collaboratorId;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->provisionSchema($this->em);

        $this->tenant = TenantId::generate();
        new InitializeDefaultRoles(new DoctrineRoleRepository($this->em))->forTenant($this->tenant);

        $hasher = new SodiumPasswordHasher();
        $this->em->persist(new Tenant($this->tenant, 'Agence A'));
        $this->em->persist(new User($this->tenant, 'marc@agence.test', $hasher->hash(self::PASSWORD), ['Chef de projet']));
        $collab = new User($this->tenant, 'alice@agence.test', $hasher->hash(self::PASSWORD), ['Collaborateur']);
        $collab->rename('Alice', 'Martin');
        $this->em->persist($collab);
        $this->collaboratorId = $collab->id();
        $this->em->persist(new ProjectAssignment($this->tenant, '018f9c4e-0000-7000-8000-0000000000f1', $this->collaboratorId, 'Dev', 5, new DateTimeImmutable('2026-07-01'), new DateTimeImmutable('2026-07-31')));
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        $this->dropSchema($this->em);
        $this->em->close();
        parent::tearDown();
    }

    public function testChefDeProjetSeesWorkloadPlan(): void
    {
        $this->login('marc@agence.test');
        $this->client->request('GET', '/planification/charge?period=2026-07');

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Plan de charge', $content);
        self::assertStringContainsString('Alice Martin', $content);
        self::assertStringContainsString('Charge ferme', $content);
    }

    public function testCollaboratorForbidden(): void
    {
        $this->login('alice@agence.test');
        $this->client->request('GET', '/planification/charge');
        self::assertResponseStatusCodeSame(403);
    }

    private function login(string $email): void
    {
        $this->client->request(
            'POST',
            '/api/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => $email, 'password' => self::PASSWORD], JSON_THROW_ON_ERROR),
        );
        self::assertResponseIsSuccessful();
    }
}
