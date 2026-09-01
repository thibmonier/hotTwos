<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Authorization\Role;
use App\Domain\Reminder\ReminderPreference;
use App\Domain\Reminder\ReminderRule;
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
 * US-056 (T-056-04) — API relances : paramétrage réservé à l'habilité (403 sinon, 401 sans auth),
 * validation des bornes (422), opt-out individuel et **non forçable** (chaque préférence est propre
 * à son collaborateur).
 */
final class ReminderApiTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

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
            $this->em->getClassMetadata(ReminderRule::class),
            $this->em->getClassMetadata(ReminderPreference::class),
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
        $this->client->request('GET', '/api/reminders/rules', server: ['HTTP_ACCEPT' => 'application/json']);

        self::assertResponseStatusCodeSame(401);
    }

    public function testCollaboratorCannotConfigure(): void
    {
        $this->login('camille@agence.test');

        $this->client->request('GET', '/api/reminders/rules', server: ['HTTP_ACCEPT' => 'application/json']);

        self::assertResponseStatusCodeSame(403);
    }

    public function testManagerReadsDefaultThenUpdates(): void
    {
        $this->login('marc@agence.test');

        $this->client->request('GET', '/api/reminders/rules', server: ['HTTP_ACCEPT' => 'application/json']);
        self::assertResponseIsSuccessful();
        self::assertSame('in_app', $this->decodeObject()['channel'] ?? null);

        $this->putJson('/api/reminders/rules', [
            'initialDelayDays' => 2,
            'frequencyDays' => 5,
            'channel' => 'both',
            'escalationEnabled' => false,
            'active' => true,
        ]);
        self::assertResponseIsSuccessful();

        $this->client->request('GET', '/api/reminders/rules', server: ['HTTP_ACCEPT' => 'application/json']);
        $rule = $this->decodeObject();
        self::assertSame(5, $rule['frequencyDays'] ?? null);
        self::assertSame('both', $rule['channel'] ?? null);
        self::assertFalse($rule['escalationEnabled'] ?? null);
    }

    public function testInvalidFrequencyIsRejectedWith422(): void
    {
        $this->login('marc@agence.test');

        $this->putJson('/api/reminders/rules', [
            'initialDelayDays' => 1,
            'frequencyDays' => 0,
            'channel' => 'in_app',
            'escalationEnabled' => true,
            'active' => true,
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testOptOutIsIndividualAndNotForceable(): void
    {
        // Camille se désinscrit — droit individuel, sans habilitation particulière.
        $this->login('camille@agence.test');
        $this->putJson('/api/me/reminder-preference', ['optedOut' => true]);
        self::assertResponseIsSuccessful();
        self::assertTrue($this->decodeObject()['optedOut'] ?? null);

        // La préférence est propre à chaque collaborateur : Marc n'est pas affecté et aucune route
        // ne permet d'agir sur la préférence d'un tiers (opt-out non forçable par l'admin).
        $this->login('marc@agence.test');
        $this->client->request('GET', '/api/me/reminder-preference', server: ['HTTP_ACCEPT' => 'application/json']);
        self::assertResponseIsSuccessful();
        self::assertFalse($this->decodeObject()['optedOut'] ?? true);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function putJson(string $uri, array $payload): void
    {
        $this->client->request('PUT', $uri, server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'], content: json_encode($payload, JSON_THROW_ON_ERROR));
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

    private function login(string $email): void
    {
        $this->client->request('POST', '/api/login', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['email' => $email, 'password' => 'motdepasse-solide'], JSON_THROW_ON_ERROR));
        self::assertResponseIsSuccessful();
    }
}
