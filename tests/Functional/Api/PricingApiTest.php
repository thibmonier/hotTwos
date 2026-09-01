<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Authorization\Role;
use App\Domain\Pricing\Profile;
use App\Domain\Pricing\ProfileRate;
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
 * US-011 (T-011-05/07) — l'API de tarification exige l'habilitation ADMIN (403), applique les
 * règles métier (chevauchement, mode invalide → 422) et ne supprime jamais (DELETE = désactivation).
 */
final class PricingApiTest extends WebTestCase
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
            $this->em->getClassMetadata(Profile::class),
            $this->em->getClassMetadata(ProfileRate::class),
        ];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        $this->tenant = TenantId::generate();
        new InitializeDefaultRoles(new DoctrineRoleRepository($this->em))->forTenant($this->tenant);

        $hasher = new SodiumPasswordHasher();
        $this->em->persist(new Tenant($this->tenant, 'Agence A'));
        $this->em->persist(new User($this->tenant, 'admin@agence.test', $hasher->hash('motdepasse-solide'), ['Administrateur']));
        $this->em->persist(new User($this->tenant, 'marc@agence.test', $hasher->hash('motdepasse-solide'), ['Chef de projet']));
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->em->close();
        parent::tearDown();
    }

    public function testAdminCreatesAndListsProfiles(): void
    {
        $this->login('admin@agence.test');

        $id = $this->createProfile('Développeur senior', 'loaded');
        self::assertNotSame('', $id);

        $this->client->request('GET', '/api/profiles', server: ['HTTP_ACCEPT' => 'application/json']);
        self::assertResponseIsSuccessful();
        $list = $this->decodeList();
        self::assertCount(1, $list);
        self::assertSame('loaded', $list[0]['calculationMode'] ?? null);
    }

    public function testNonAdminCannotCreateProfile(): void
    {
        $this->login('marc@agence.test');

        $this->postJson('/api/profiles', ['name' => 'X', 'calculationMode' => 'direct']);

        self::assertResponseStatusCodeSame(403);
    }

    public function testUnauthenticatedIsRejected(): void
    {
        $this->postJson('/api/profiles', ['name' => 'X', 'calculationMode' => 'direct']);

        self::assertResponseStatusCodeSame(401);
    }

    public function testInvalidCalculationModeIsRejected(): void
    {
        $this->login('admin@agence.test');

        $this->postJson('/api/profiles', ['name' => 'X', 'calculationMode' => 'invalide']);

        self::assertResponseStatusCodeSame(422);
    }

    public function testDefineRateThenHistoryThenOverlapRejected(): void
    {
        $this->login('admin@agence.test');
        $profileId = $this->createProfile('Consultant', 'direct');

        $this->postJson('/api/profile-rates', [
            'profileId' => $profileId,
            'effectiveFrom' => '2099-01-01',
            'effectiveTo' => '2099-07-01',
            'costPriceCents' => 45000,
            'sellingPriceCents' => 78000,
        ]);
        self::assertResponseStatusCodeSame(201);

        $this->client->request('GET', '/api/profile-rates?profileId='.$profileId, server: ['HTTP_ACCEPT' => 'application/json']);
        self::assertResponseIsSuccessful();
        self::assertCount(1, $this->decodeList());

        // Chevauchement → 422.
        $this->postJson('/api/profile-rates', [
            'profileId' => $profileId,
            'effectiveFrom' => '2099-03-01',
            'effectiveTo' => '2099-09-01',
            'costPriceCents' => 46000,
            'sellingPriceCents' => 80000,
        ]);
        self::assertResponseStatusCodeSame(422);
    }

    public function testDeleteDeactivatesTheProfile(): void
    {
        $this->login('admin@agence.test');
        $profileId = $this->createProfile('Junior', 'direct');

        $this->client->request('DELETE', '/api/profiles/'.$profileId);
        self::assertResponseStatusCodeSame(204);

        $this->client->request('GET', '/api/profiles', server: ['HTTP_ACCEPT' => 'application/json']);
        self::assertFalse($this->decodeList()[0]['active'] ?? true);
    }

    private function createProfile(string $name, string $mode): string
    {
        $this->postJson('/api/profiles', ['name' => $name, 'calculationMode' => $mode]);
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
