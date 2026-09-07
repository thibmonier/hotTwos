<?php

declare(strict_types=1);

namespace App\Tests\Functional\Web;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Audit\AuditAction;
use App\Domain\Audit\ConfigAuditEntry;
use App\Domain\Authorization\Role;
use App\Domain\Budget\ChargeDriftThreshold;
use App\Domain\Calendar\ClosurePeriod;
use App\Domain\Calendar\Holiday;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Infrastructure\Persistence\Doctrine\DoctrineRoleRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\SodiumPasswordHasher;

/**
 * US-020 (EF-REF-33, CA-1/CA-2/CA-3/CA-4/CA-6) — journal d'audit : journalisation des changements de
 * paramétrage, filtrage, isolation multi-tenant, gating VIEW_AUDIT_LOG.
 */
final class AuditLogTest extends WebTestCase
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
            $this->em->getClassMetadata(ConfigAuditEntry::class),
            $this->em->getClassMetadata(Holiday::class),
            $this->em->getClassMetadata(ClosurePeriod::class),
            $this->em->getClassMetadata(ChargeDriftThreshold::class),
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

    public function testThresholdChangeIsLoggedAndVisible(): void
    {
        $this->login('admin@agence.test');
        $crawler = $this->client->request('GET', '/finance/config-derive-charge');
        $token = $crawler->filter('input[name="_token"]')->attr('value') ?? '';
        $this->client->request('POST', '/finance/config-derive-charge', [
            '_token' => $token,
            'alert_forfait' => '8', 'escalation_forfait' => '15',
            'alert_regie' => '15', 'escalation_regie' => '25',
        ]);
        self::assertResponseRedirects();

        $this->client->request('GET', '/parametrage/audit');
        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Seuil de dérive de charge', $content);
        self::assertStringContainsString('seuil_alerte', $content);
    }

    public function testFilterByObjectType(): void
    {
        // Deux types d'objet journalisés directement.
        $this->em->persist(new ConfigAuditEntry($this->tenant, '018f9c4e-0000-7000-8000-0000000000a1', AuditAction::CREATION, 'Jour férié', 'Noël (2027-12-25)', null, null, null, new DateTimeImmutable('2027-01-01 09:00:00')));
        $this->em->persist(new ConfigAuditEntry($this->tenant, '018f9c4e-0000-7000-8000-0000000000a1', AuditAction::CREATION, 'Compétence', 'React.js', null, null, null, new DateTimeImmutable('2027-01-02 09:00:00')));
        $this->em->flush();

        $this->login('admin@agence.test');
        $this->client->request('GET', '/parametrage/audit', ['objectType' => 'Jour férié']);
        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Noël', $content);
        self::assertStringNotContainsString('React.js', $content);
    }

    public function testTenantIsolation(): void
    {
        $other = TenantId::generate();
        $this->em->persist(new ConfigAuditEntry($other, '018f9c4e-0000-7000-8000-0000000000b2', AuditAction::CREATION, 'Compétence', 'SecretDunAutreTenant', null, null, null, new DateTimeImmutable('2027-01-01 09:00:00')));
        $this->em->flush();

        $this->login('admin@agence.test');
        $this->client->request('GET', '/parametrage/audit');
        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('SecretDunAutreTenant', (string) $this->client->getResponse()->getContent());
    }

    public function testNonAuthorizedForbidden(): void
    {
        $this->login('marc@agence.test'); // Chef de projet : pas de VIEW_AUDIT_LOG
        $this->client->request('GET', '/parametrage/audit');
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
