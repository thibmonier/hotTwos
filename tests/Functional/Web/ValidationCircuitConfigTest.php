<?php

declare(strict_types=1);

namespace App\Tests\Functional\Web;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Authorization\Role;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Domain\Validation\AbsenceValidationCircuit;
use App\Infrastructure\Persistence\Doctrine\DoctrineRoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\SodiumPasswordHasher;

/**
 * US-017 (EF-REF-25, CA-3/CA-6) — configuration du circuit de validation des absences : enregistrement
 * 2 étapes, gating MANAGE_ORGANIZATION.
 */
final class ValidationCircuitConfigTest extends WebTestCase
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
            $this->em->getClassMetadata(AbsenceValidationCircuit::class),
        ];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

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
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->em->close();
        parent::tearDown();
    }

    public function testAdminConfiguresTwoStepCircuit(): void
    {
        $this->login('admin@agence.test');
        $crawler = $this->client->request('GET', '/parametrage/circuits-validation');
        self::assertResponseIsSuccessful();
        $token = $crawler->filter('input[name="_token"]')->attr('value') ?? '';

        $this->client->request('POST', '/parametrage/circuits-validation', ['_token' => $token, 'step1' => 'Chef de projet', 'step2' => 'Administrateur']);
        self::assertResponseRedirects();

        $circuit = $this->em->createQuery('SELECT c FROM '.AbsenceValidationCircuit::class.' c')->getOneOrNullResult();
        self::assertInstanceOf(AbsenceValidationCircuit::class, $circuit);
        self::assertSame(['Chef de projet', 'Administrateur'], $circuit->steps());
    }

    public function testNonAdminForbidden(): void
    {
        $this->login('marc@agence.test');
        $this->client->request('GET', '/parametrage/circuits-validation');
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
