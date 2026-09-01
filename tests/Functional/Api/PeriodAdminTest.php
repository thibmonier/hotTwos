<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Authorization\Role;
use App\Domain\Period\AccountingPeriod;
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
 * US-057 (T-057-07/08) — l'écran d'administration des périodes exige `MANAGE_PERIODS` (401/403) et
 * clôture une période via une confirmation, verrouillant ensuite ses imputations.
 */
final class PeriodAdminTest extends WebTestCase
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
            $this->em->getClassMetadata(TimeEntry::class),
            $this->em->getClassMetadata(AccountingPeriod::class),
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

    public function testUnauthenticatedIsRejected(): void
    {
        $this->client->request('GET', '/administration/periodes');

        self::assertResponseStatusCodeSame(401);
    }

    public function testNonAdminIsForbidden(): void
    {
        $this->login('marc@agence.test');

        $this->client->request('GET', '/administration/periodes');

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminClosesAPeriodViaConfirmation(): void
    {
        $this->login('admin@agence.test');

        $crawler = $this->client->request('GET', '/administration/periodes');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Périodes comptables');

        $form = $crawler->selectButton('Clôturer la période')->form();
        $form['period'] = '2026-08';
        $form['confirmation'] = '2026-08';
        $this->client->submit($form);

        self::assertResponseRedirects('/administration/periodes');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.flash-success', 'clôturée');
        // La période clôturée apparaît désormais dans la liste avec le statut « Clôturée ».
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('2026-08', $content);
        self::assertStringContainsString('Clôturée', $content);
    }

    public function testConfirmationMismatchDoesNotClose(): void
    {
        $this->login('admin@agence.test');

        $crawler = $this->client->request('GET', '/administration/periodes');
        $form = $crawler->selectButton('Clôturer la période')->form();
        $form['period'] = '2026-08';
        $form['confirmation'] = '2026-07';
        $this->client->submit($form);
        $this->client->followRedirect();

        self::assertSelectorTextContains('.flash-error', 'Confirmation invalide');
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
