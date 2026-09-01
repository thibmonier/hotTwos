<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Organization\OrgMembership;
use App\Domain\Organization\OrgUnit;
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
 * US-010 (T-010-05/07) — l'API d'organisation exige l'habilitation ADMIN (403 sinon),
 * applique les règles métier (chevauchement → 422) et ne supprime jamais (DELETE = désactivation).
 */
final class OrganizationApiTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private TenantId $tenant;
    private string $collaboratorId;

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
            $this->em->getClassMetadata(OrgUnit::class),
            $this->em->getClassMetadata(OrgMembership::class),
        ];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        $this->tenant = TenantId::generate();
        new InitializeDefaultRoles(new DoctrineRoleRepository($this->em))->forTenant($this->tenant);

        $hasher = new SodiumPasswordHasher();
        $collaborator = new User($this->tenant, 'camille@agence.test', $hasher->hash('motdepasse-solide'), ['Collaborateur']);
        $this->collaboratorId = $collaborator->id();
        $this->em->persist(new Tenant($this->tenant, 'Agence A'));
        $this->em->persist(new User($this->tenant, 'admin@agence.test', $hasher->hash('motdepasse-solide'), ['Administrateur']));
        $this->em->persist(new User($this->tenant, 'marc@agence.test', $hasher->hash('motdepasse-solide'), ['Chef de projet']));
        $this->em->persist($collaborator);
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->em->close();
        parent::tearDown();
    }

    public function testAdminCreatesAndListsUnits(): void
    {
        $this->login('admin@agence.test');

        $id = $this->createUnit('Direction générale');
        self::assertNotSame('', $id);

        $this->client->request('GET', '/api/org-units', server: ['HTTP_ACCEPT' => 'application/json']);
        self::assertResponseIsSuccessful();
        $list = $this->decodeList();
        self::assertCount(1, $list);
        self::assertSame('Direction générale', $list[0]['name'] ?? null);
    }

    public function testNonAdminCannotCreateUnit(): void
    {
        $this->login('marc@agence.test');

        $this->postJson('/api/org-units', ['name' => 'Direction']);

        self::assertResponseStatusCodeSame(403);
    }

    public function testUnauthenticatedIsRejected(): void
    {
        $this->postJson('/api/org-units', ['name' => 'Direction']);

        self::assertResponseStatusCodeSame(401);
    }

    public function testOverlappingAttachmentIsRejectedWith422(): void
    {
        $this->login('admin@agence.test');
        $unitId = $this->createUnit('Équipe Data');

        $this->postJson('/api/org-memberships', [
            'userId' => $this->collaboratorId,
            'orgUnitId' => $unitId,
            'effectiveFrom' => '2026-01-01',
            'effectiveTo' => '2026-07-01',
        ]);
        self::assertResponseStatusCodeSame(201);

        $this->postJson('/api/org-memberships', [
            'userId' => $this->collaboratorId,
            'orgUnitId' => $unitId,
            'effectiveFrom' => '2026-03-01',
        ]);
        self::assertResponseStatusCodeSame(422);
    }

    public function testDeleteDeactivatesTheUnit(): void
    {
        $this->login('admin@agence.test');
        $unitId = $this->createUnit('Direction');

        $this->client->request('DELETE', '/api/org-units/'.$unitId);
        self::assertResponseStatusCodeSame(204);

        $this->client->request('GET', '/api/org-units', server: ['HTTP_ACCEPT' => 'application/json']);
        $list = $this->decodeList();
        self::assertFalse($list[0]['active'] ?? true);
    }

    public function testAdminSeesTheOrganizationScreen(): void
    {
        $this->login('admin@agence.test');

        $this->client->request('GET', '/organisation');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Structure organisationnelle');
    }

    public function testNonAdminIsForbiddenFromTheScreen(): void
    {
        $this->login('marc@agence.test');

        $this->client->request('GET', '/organisation');

        self::assertResponseStatusCodeSame(403);
    }

    private function createUnit(string $name): string
    {
        $this->postJson('/api/org-units', ['name' => $name]);
        self::assertResponseStatusCodeSame(201);

        $id = $this->decodeObject()['id'] ?? null;
        self::assertIsString($id);

        return $id;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function postJson(string $uri, array $payload): void
    {
        $this->client->request(
            'POST',
            $uri,
            server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            content: json_encode($payload, JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeObject(): array
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);

        return $decoded;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function decodeList(): array
    {
        /** @var list<array<string, mixed>> $decoded */
        $decoded = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);

        return $decoded;
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
