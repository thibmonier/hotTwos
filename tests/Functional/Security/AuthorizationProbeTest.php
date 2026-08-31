<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Authorization\Role;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Infrastructure\Persistence\Doctrine\DoctrineRoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\SodiumPasswordHasher;

/**
 * US-003 — le contrôle d'habilitation est fait côté serveur, jamais délégué à l'UI
 * (ARC-106). Prouve, de bout en bout, HAB-1 (le chef de projet n'accède pas au coût)
 * et l'accès accordé au Resource Manager (CA-1, CA-3).
 */
final class AuthorizationProbeTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private TenantId $tenant;

    /** @var list<\Doctrine\ORM\Mapping\ClassMetadata<object>> */
    private array $schema;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        $this->schema = [
            $this->em->getClassMetadata(Tenant::class),
            $this->em->getClassMetadata(User::class),
            $this->em->getClassMetadata(Role::class),
        ];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        $this->tenant = TenantId::generate();
        new InitializeDefaultRoles(new DoctrineRoleRepository($this->em))->forTenant($this->tenant);

        $hasher = new SodiumPasswordHasher();
        $this->em->persist(new Tenant($this->tenant, 'Agence A'));
        $this->em->persist(new User($this->tenant, 'sophie@agence.test', $hasher->hash('motdepasse-solide'), ['Resource Manager']));
        $this->em->persist(new User($this->tenant, 'marc@agence.test', $hasher->hash('motdepasse-solide'), ['Chef de projet']));
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->em->close();
        parent::tearDown();
    }

    public function testResourceManagerIsGrantedCollaboratorCost(): void
    {
        $this->login('sophie@agence.test');

        $this->client->request('GET', '/api/_probe/collaborator-cost');

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        self::assertTrue($payload['granted'] ?? null);
        self::assertSame('pool', $payload['scope'] ?? null);
    }

    public function testChefDeProjetIsForbiddenFromCollaboratorCost(): void
    {
        $this->login('marc@agence.test');

        $this->client->request('GET', '/api/_probe/collaborator-cost');

        self::assertResponseStatusCodeSame(403);
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        self::assertSame('Permission refusée : view:collaborator_cost', $payload['error'] ?? null);
    }

    public function testProbeRequiresAuthentication(): void
    {
        $this->client->request('GET', '/api/_probe/collaborator-cost');

        self::assertResponseStatusCodeSame(401);
    }

    private function login(string $email): void
    {
        $this->client->request(
            'POST',
            '/api/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => $email, 'password' => 'motdepasse-solide'], JSON_THROW_ON_ERROR),
        );
        self::assertResponseIsSuccessful();
    }
}
