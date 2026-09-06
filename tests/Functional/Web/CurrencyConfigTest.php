<?php

declare(strict_types=1);

namespace App\Tests\Functional\Web;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Authorization\Role;
use App\Domain\Currency\ExchangeRate;
use App\Domain\Currency\ReferenceCurrency;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Infrastructure\Persistence\Doctrine\DoctrineExchangeRateRepository;
use App\Infrastructure\Persistence\Doctrine\DoctrineReferenceCurrencyRepository;
use App\Infrastructure\Persistence\Doctrine\DoctrineRoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\SodiumPasswordHasher;

/**
 * US-016 (EF-REF-22) — l'administrateur configure la devise de référence et un taux de change.
 */
final class CurrencyConfigTest extends WebTestCase
{
    private const string PASSWORD = 'motdepasse-solide';

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
            $this->em->getClassMetadata(ReferenceCurrency::class),
            $this->em->getClassMetadata(ExchangeRate::class),
        ];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        $this->tenant = TenantId::generate();
        new InitializeDefaultRoles(new DoctrineRoleRepository($this->em))->forTenant($this->tenant);
        $this->em->persist(new Tenant($this->tenant, 'Agence A'));
        $this->em->persist(new User($this->tenant, 'admin@agence.test', new SodiumPasswordHasher()->hash(self::PASSWORD), ['Administrateur']));
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->em->close();
        parent::tearDown();
    }

    public function testAdminConfiguresReferenceAndRate(): void
    {
        $this->login('admin@agence.test');
        $crawler = $this->client->request('GET', '/finance/config-devises');
        self::assertResponseIsSuccessful();
        $token = $crawler->filter('form[action="/finance/config-devises/reference"] input[name="_token"]')->attr('value') ?? '';

        $this->client->request('POST', '/finance/config-devises/reference', ['_token' => $token, 'code' => 'CHF']);
        self::assertResponseRedirects('/finance/config-devises');
        self::assertSame('CHF', new DoctrineReferenceCurrencyRepository($this->em)->findForTenant($this->tenant)?->code());

        $this->client->request('POST', '/finance/config-devises/taux', ['_token' => $token, 'code' => 'EUR', 'from' => '2027-01-01', 'rate' => '0.95']);
        self::assertResponseRedirects('/finance/config-devises');
        $rates = new DoctrineExchangeRateRepository($this->em)->findForCurrency($this->tenant, 'EUR');
        self::assertCount(1, $rates);
        self::assertSame(950, $rates[0]->rateToReferenceMillis());
    }

    private function login(string $email): void
    {
        $this->client->request('POST', '/api/login', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['email' => $email, 'password' => self::PASSWORD], JSON_THROW_ON_ERROR));
        self::assertResponseIsSuccessful();
    }
}
