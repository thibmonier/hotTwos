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
 * US-058 (T-058-04) — l'écran de complétude exige une authentification (401) et s'adapte au
 * périmètre : un chef de projet voit l'équipe, un collaborateur voit ses propres semaines.
 */
final class CompletenessPageTest extends WebTestCase
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
        $this->client->request('GET', '/completude');

        self::assertResponseStatusCodeSame(401);
    }

    public function testManagerSeesTeamPerimeter(): void
    {
        $this->login('marc@agence.test');

        $this->client->request('GET', '/completude');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Complétude de saisie');
        self::assertStringContainsString('Périmètre équipe', (string) $this->client->getResponse()->getContent());
    }

    public function testCollaboratorSeesOwnPerimeter(): void
    {
        $this->login('camille@agence.test');

        $this->client->request('GET', '/completude');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Vos semaines', (string) $this->client->getResponse()->getContent());
    }

    /**
     * US-067 (T-067-05, CA-1) — un collaborateur nommé est affiché « Prénom Nom », plus par son e-mail.
     */
    public function testDisplayNameShownInsteadOfEmail(): void
    {
        $camille = $this->em->getRepository(User::class)->findOneBy(['email' => 'camille@agence.test']);
        self::assertInstanceOf(User::class, $camille);
        $camille->rename('Camille', 'Martin');
        $this->em->flush();

        $this->login('camille@agence.test');
        $this->client->request('GET', '/completude');

        self::assertResponseIsSuccessful();
        $body = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Camille Martin', $body);
    }

    /**
     * US-069 (T-069-02) — les en-têtes de semaine sont humanisés (n° de semaine ISO + date courte),
     * plus de date ISO technique « Sem. 2026-08-10 ».
     */
    public function testWeekHeadersAreHumanReadable(): void
    {
        $this->login('camille@agence.test');

        $this->client->request('GET', '/completude');

        self::assertResponseIsSuccessful();
        $body = (string) $this->client->getResponse()->getContent();
        self::assertDoesNotMatchRegularExpression('/Sem\. \d{4}-\d{2}-\d{2}/', $body);
        self::assertMatchesRegularExpression('/Sem\. \d{1,2}\b/', $body);
    }

    private function login(string $email): void
    {
        $this->client->request('POST', '/api/login', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['email' => $email, 'password' => 'motdepasse-solide'], JSON_THROW_ON_ERROR));
        self::assertResponseIsSuccessful();
    }
}
