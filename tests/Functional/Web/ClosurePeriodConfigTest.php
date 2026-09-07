<?php

declare(strict_types=1);

namespace App\Tests\Functional\Web;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Calendar\ClosurePeriod;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Infrastructure\Persistence\Doctrine\DoctrineRoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\SodiumPasswordHasher;
use App\Tests\Support\Schema\ProvisionsFullSchema;

/**
 * US-022 (EF-REF-9, CA-3/CA-5/CA-6) — paramétrage des fermetures : CRUD admin, refus fin<début, gating.
 */
final class ClosurePeriodConfigTest extends WebTestCase
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

    public function testAdminAddsAndListsClosure(): void
    {
        $this->login('admin@agence.test');
        $crawler = $this->client->request('GET', '/parametrage/fermetures');
        self::assertResponseIsSuccessful();
        $token = $crawler->filter('input[name="_token"]')->attr('value') ?? '';

        $this->client->request('POST', '/parametrage/fermetures', ['_token' => $token, 'start' => '2027-12-23', 'end' => '2027-12-31', 'label' => 'Vacances de Noel']);
        self::assertResponseRedirects();

        $this->client->request('GET', '/parametrage/fermetures');
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Vacances de Noel', $content);
        self::assertStringContainsString('23/12/2027', $content);
    }

    public function testEndBeforeStartRejected(): void
    {
        $this->login('admin@agence.test');
        $crawler = $this->client->request('GET', '/parametrage/fermetures');
        $token = $crawler->filter('input[name="_token"]')->attr('value') ?? '';

        $this->client->request('POST', '/parametrage/fermetures', ['_token' => $token, 'start' => '2027-12-31', 'end' => '2027-12-23', 'label' => 'Invalide']);
        self::assertResponseRedirects();

        $count = (int) $this->em->createQuery('SELECT COUNT(c.id) FROM '.ClosurePeriod::class.' c')->getSingleScalarResult();
        self::assertSame(0, $count);
    }

    public function testNonAdminForbidden(): void
    {
        $this->login('marc@agence.test');
        $this->client->request('GET', '/parametrage/fermetures');
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
