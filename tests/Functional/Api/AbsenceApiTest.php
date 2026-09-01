<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Absence\AbsenceRequest;
use App\Domain\Absence\AbsenceType;
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
 * US-054 (T-054-05/07) — API absences : déclaration (201), liste et compteurs pour soi-même,
 * décision réservée au manager habilité (403 sinon), authentification requise (401).
 */
final class AbsenceApiTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private string $typeId;

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
            $this->em->getClassMetadata(AbsenceType::class),
            $this->em->getClassMetadata(AbsenceRequest::class),
        ];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        $tenant = TenantId::generate();
        new InitializeDefaultRoles(new DoctrineRoleRepository($this->em))->forTenant($tenant);

        $hasher = new SodiumPasswordHasher();
        $this->em->persist(new Tenant($tenant, 'Agence A'));
        $this->em->persist(new User($tenant, 'camille@agence.test', $hasher->hash('motdepasse-solide'), ['Collaborateur']));
        $this->em->persist(new User($tenant, 'marc@agence.test', $hasher->hash('motdepasse-solide'), ['Chef de projet']));
        $type = new AbsenceType($tenant, 'Congés payés');
        $this->typeId = $type->id();
        $this->em->persist($type);
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
        $this->postJson('/api/absences', ['typeId' => $this->typeId, 'startDate' => '2026-09-01', 'endDate' => '2026-09-05']);

        self::assertResponseStatusCodeSame(401);
    }

    public function testDeclareListAndBalance(): void
    {
        $this->login('camille@agence.test');

        $this->postJson('/api/absences', ['typeId' => $this->typeId, 'startDate' => '2026-09-01', 'endDate' => '2026-09-05']);
        self::assertResponseStatusCodeSame(201);

        $this->client->request('GET', '/api/absences', server: ['HTTP_ACCEPT' => 'application/json']);
        self::assertResponseIsSuccessful();
        self::assertCount(1, $this->decodeList());

        $this->client->request('GET', '/api/absences/balance', server: ['HTTP_ACCEPT' => 'application/json']);
        self::assertResponseIsSuccessful();
        $balance = $this->decodeObject();
        self::assertSame(5.0, $balance['pending'] ?? null);
        self::assertSame(20.0, $balance['projectedBalance'] ?? null);
    }

    public function testManagerDecidesButCollaboratorCannot(): void
    {
        $this->login('camille@agence.test');
        $this->postJson('/api/absences', ['typeId' => $this->typeId, 'startDate' => '2026-09-01', 'endDate' => '2026-09-05']);
        $id = $this->decodeObject()['id'] ?? null;
        self::assertIsString($id);

        // Le collaborateur ne peut pas décider.
        $this->postJson('/api/absences/'.$id.'/decision', ['approved' => true]);
        self::assertResponseStatusCodeSame(403);

        // Le manager valide.
        $this->login('marc@agence.test');
        $this->postJson('/api/absences/'.$id.'/decision', ['approved' => true]);
        self::assertResponseIsSuccessful();
        self::assertSame('validated', $this->decodeObject()['status'] ?? null);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function postJson(string $uri, array $payload): void
    {
        $this->client->request('POST', $uri, server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'], content: json_encode($payload, JSON_THROW_ON_ERROR));
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
        $this->client->request('POST', '/api/login', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['email' => $email, 'password' => 'motdepasse-solide'], JSON_THROW_ON_ERROR));
        self::assertResponseIsSuccessful();
    }
}
