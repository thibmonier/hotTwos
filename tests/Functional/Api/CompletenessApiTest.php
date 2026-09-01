<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Absence\AbsenceRequest;
use App\Domain\Authorization\Role;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use App\Domain\Timesheet\TimeEntry;
use App\Domain\User\User;
use App\Infrastructure\Persistence\Doctrine\DoctrineRoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\SodiumPasswordHasher;

/**
 * US-058 (T-058-03/05) — API de complétude : périmètre soi-même par défaut, `?scope=team` réservé
 * (403), export CSV, authentification requise (401), état vide sans erreur.
 */
final class CompletenessApiTest extends WebTestCase
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
            $this->em->getClassMetadata(TimeEntry::class),
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
        $this->client->request('GET', '/api/completude');

        self::assertResponseStatusCodeSame(401);
    }

    public function testCollaboratorSeesOwnGridWithoutError(): void
    {
        $this->login('camille@agence.test');

        $this->client->request('GET', '/api/completude', server: ['HTTP_ACCEPT' => 'application/json']);

        self::assertResponseIsSuccessful();
        /** @var list<array<string, mixed>> $grid */
        $grid = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertCount(4, $grid, 'Quatre semaines glissantes pour le collaborateur.');
    }

    public function testCollaboratorCannotAccessTeamScope(): void
    {
        $this->login('camille@agence.test');

        $this->client->request('GET', '/api/completude?scope=team', server: ['HTTP_ACCEPT' => 'application/json']);

        self::assertResponseStatusCodeSame(403);
    }

    public function testManagerAccessesTeamScopeAndCsv(): void
    {
        $this->login('marc@agence.test');

        $this->client->request('GET', '/api/completude?scope=team', server: ['HTTP_ACCEPT' => 'application/json']);
        self::assertResponseIsSuccessful();

        $this->client->request('GET', '/api/completude/export?scope=team');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('text/csv', (string) $this->client->getResponse()->headers->get('Content-Type'));
        self::assertStringContainsString('Collaborateur;Semaine', (string) $this->client->getResponse()->getContent());
    }

    private function login(string $email): void
    {
        $this->client->request('POST', '/api/login', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['email' => $email, 'password' => 'motdepasse-solide'], JSON_THROW_ON_ERROR));
        self::assertResponseIsSuccessful();
    }
}
