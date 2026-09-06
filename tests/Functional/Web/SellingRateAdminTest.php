<?php

declare(strict_types=1);

namespace App\Tests\Functional\Web;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Authorization\Role;
use App\Domain\Client\Client;
use App\Domain\Pricing\CalculationMode;
use App\Domain\Pricing\Profile;
use App\Domain\Pricing\RateScope;
use App\Domain\Pricing\SellingRate;
use App\Domain\Project\Project;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Infrastructure\Persistence\Doctrine\DoctrineRoleRepository;
use App\Infrastructure\Persistence\Doctrine\DoctrineSellingRateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\SodiumPasswordHasher;

/**
 * US-015 (EF-REF-19) — l'administrateur définit une surcharge de taux de vente (client/projet) depuis
 * l'écran des profils. Gating MANAGE_PRICING.
 */
final class SellingRateAdminTest extends WebTestCase
{
    private const string PASSWORD = 'motdepasse-solide';

    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private TenantId $tenant;
    private string $profileId;
    private string $clientId;

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
            $this->em->getClassMetadata(Client::class),
            $this->em->getClassMetadata(Project::class),
            $this->em->getClassMetadata(SellingRate::class),
        ];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        $this->tenant = TenantId::generate();
        new InitializeDefaultRoles(new DoctrineRoleRepository($this->em))->forTenant($this->tenant);

        $this->em->persist(new Tenant($this->tenant, 'Agence A'));
        $this->em->persist(new User($this->tenant, 'admin@agence.test', new SodiumPasswordHasher()->hash(self::PASSWORD), ['Administrateur']));

        $profile = new Profile($this->tenant, 'Senior', CalculationMode::DIRECT);
        $this->em->persist($profile);
        $this->profileId = $profile->id();

        $client = new Client($this->tenant, 'ACME');
        $this->em->persist($client);
        $this->clientId = $client->id();

        $this->em->flush();
    }

    protected function tearDown(): void
    {
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->em->close();
        parent::tearDown();
    }

    public function testAdminDefinesClientSellingRate(): void
    {
        $this->login('admin@agence.test');
        $crawler = $this->client->request('GET', '/profils');
        self::assertResponseIsSuccessful();
        $token = $crawler->filter('form[action="/profils/taux-vente"] input[name="_token"]')->attr('value') ?? '';

        $this->client->request('POST', '/profils/taux-vente', [
            '_token' => $token,
            'profileId' => $this->profileId,
            'scope' => 'client',
            'scopeRefId' => $this->clientId,
            'from' => '2027-07-01',
            'sellingEuros' => '850',
        ]);
        self::assertResponseRedirects('/profils');

        $rates = new DoctrineSellingRateRepository($this->em)->findForScope($this->tenant, $this->profileId, RateScope::CLIENT, $this->clientId);
        self::assertCount(1, $rates);
        self::assertSame(850_00, $rates[0]->sellingPriceCents());
    }

    private function login(string $email): void
    {
        $this->client->request('POST', '/api/login', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['email' => $email, 'password' => self::PASSWORD], JSON_THROW_ON_ERROR));
        self::assertResponseIsSuccessful();
    }
}
