<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Authorization\Role;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Domain\Valuation\TimeEntryValuation;
use App\Infrastructure\Persistence\Doctrine\DoctrineRoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\SodiumPasswordHasher;
use DateTimeImmutable;
use DateTimeZone;

/**
 * US-060 (T-060-06) — le tableau de bord financier affiche fraîcheur, avancement, CA et bandeau
 * d'alerte ; le détail des coûts et l'audit trail du taux (données sensibles HAB-1) ne sont
 * visibles qu'aux porteurs de `VIEW_COLLABORATOR_COST`. Habilité seulement (401/403).
 */
final class ValuationDashboardTest extends WebTestCase
{
    private const string ENTRY_A = '018f9c4e-0000-7000-8000-0000000000a1';
    private const string ENTRY_B = '018f9c4e-0000-7000-8000-0000000000a2';
    private const string ENTRY_C = '018f9c4e-0000-7000-8000-0000000000a3';

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
            $this->em->getClassMetadata(TimeEntryValuation::class),
        ];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        $this->tenant = TenantId::generate();
        new InitializeDefaultRoles(new DoctrineRoleRepository($this->em))->forTenant($this->tenant);

        $hasher = new SodiumPasswordHasher();
        $this->em->persist(new Tenant($this->tenant, 'Agence A'));
        $this->em->persist(new User($this->tenant, 'marc@agence.test', $hasher->hash('motdepasse-solide'), ['Chef de projet']));
        $this->em->persist(new User($this->tenant, 'dg@agence.test', $hasher->hash('motdepasse-solide'), ['Dirigeant']));
        $this->em->persist(new User($this->tenant, 'collab@agence.test', $hasher->hash('motdepasse-solide'), ['Collaborateur']));

        $when = new DateTimeImmutable('2026-08-20 10:00:00', new DateTimeZone('UTC'));
        $rateDate = new DateTimeImmutable('2026-01-01 00:00:00', new DateTimeZone('UTC'));
        $this->em->persist(TimeEntryValuation::valued($this->tenant, self::ENTRY_A, 45000, 78000, 45000, 78000, $rateDate, $when));
        $this->em->persist(TimeEntryValuation::valued($this->tenant, self::ENTRY_B, 22500, 39000, 45000, 78000, $rateDate, $when));
        $this->em->persist(TimeEntryValuation::missingRate($this->tenant, self::ENTRY_C, $when));
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
        $this->client->request('GET', '/valorisation');

        self::assertResponseStatusCodeSame(401);
    }

    public function testCollaboratorWithoutFinancialsPermissionIsForbidden(): void
    {
        $this->login('collab@agence.test');

        $this->client->request('GET', '/valorisation');

        self::assertResponseStatusCodeSame(403);
    }

    public function testProjectManagerSeesOverviewButNotCostDetail(): void
    {
        $this->login('marc@agence.test');

        $this->client->request('GET', '/valorisation');

        self::assertResponseIsSuccessful();
        // Avancement, fraîcheur, CA et alerte visibles.
        self::assertSelectorTextContains('h1', 'tableau de bord financier');
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('2 / 3 imputations valorisées', $content);
        self::assertStringContainsString('Mise à jour il y a', $content);
        self::assertStringContainsString('1 170,00', $content); // CA = 78000 + 39000
        self::assertStringContainsString('Valorisation incomplète', $content);
        // Détail des coûts masqué (HAB-1).
        self::assertStringContainsString('réservé au contrôle de gestion', $content);
        self::assertStringNotContainsString('Marge brute', $content);
    }

    public function testExecutiveSeesCostMarginAndRateAuditTrail(): void
    {
        $this->login('dg@agence.test');

        $this->client->request('GET', '/valorisation');

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Marge brute', $content);
        self::assertStringContainsString('675,00', $content);  // coût = 45000 + 22500
        self::assertStringContainsString('495,00', $content);  // marge = 117000 - 67500
        self::assertStringContainsString('Taux appliqué', $content);
        self::assertStringNotContainsString('réservé au contrôle de gestion', $content);
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
