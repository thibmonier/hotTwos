<?php

declare(strict_types=1);

namespace App\Tests\Functional\Web;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Calendar\Holiday;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Infrastructure\Persistence\Doctrine\DoctrineRoleRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\SodiumPasswordHasher;
use App\Tests\Support\Schema\ProvisionsFullSchema;

/**
 * US-012 (EF-REF-6, CA-3/CA-5/CA-6) — paramétrage des jours fériés : CRUD admin, refus de doublon,
 * gating MANAGE_ORGANIZATION.
 */
final class HolidayConfigTest extends WebTestCase
{
    use ProvisionsFullSchema;

    private const string PASSWORD = 'motdepasse-solide';

    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private TenantId $tenant;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->provisionSchema($this->em);

        $this->tenant = TenantId::generate();
        new InitializeDefaultRoles(new DoctrineRoleRepository($this->em))->forTenant($this->tenant);

        $hasher = new SodiumPasswordHasher();
        $this->em->persist(new Tenant($this->tenant, 'Agence A'));
        $this->em->persist(new User($this->tenant, 'admin@agence.test', $hasher->hash(self::PASSWORD), ['Administrateur']));
        $this->em->persist(new User($this->tenant, 'marc@agence.test', $hasher->hash(self::PASSWORD), ['Chef de projet']));
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        $this->dropSchema($this->em);
        $this->em->close();
        parent::tearDown();
    }

    public function testAdminAddsAndListsHoliday(): void
    {
        $this->login('admin@agence.test');
        $crawler = $this->client->request('GET', '/parametrage/jours-feries');
        self::assertResponseIsSuccessful();
        $token = $crawler->filter('input[name="_token"]')->attr('value') ?? '';

        $this->client->request('POST', '/parametrage/jours-feries', ['_token' => $token, 'date' => '2027-07-14', 'label' => 'Fête nationale']);
        self::assertResponseRedirects();

        $this->client->request('GET', '/parametrage/jours-feries');
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Fête nationale', $content);
        self::assertStringContainsString('14/07/2027', $content);
    }

    public function testDuplicateHolidayRejected(): void
    {
        $this->em->persist(new Holiday($this->tenant, new DateTimeImmutable('2027-12-25'), 'Noël'));
        $this->em->flush();

        $this->login('admin@agence.test');
        $crawler = $this->client->request('GET', '/parametrage/jours-feries');
        $token = $crawler->filter('input[name="_token"]')->attr('value') ?? '';

        $this->client->request('POST', '/parametrage/jours-feries', ['_token' => $token, 'date' => '2027-12-25', 'label' => 'Noël (doublon)']);
        self::assertResponseRedirects();

        // Le doublon n'a pas été créé : le libellé de la 2e tentative est absent, l'original demeure.
        $this->client->request('GET', '/parametrage/jours-feries');
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringNotContainsString('Noël (doublon)', $content);
        self::assertStringContainsString('Noël', $content);
    }

    public function testNonAdminForbidden(): void
    {
        $this->login('marc@agence.test');
        $this->client->request('GET', '/parametrage/jours-feries');
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
