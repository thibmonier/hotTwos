<?php

declare(strict_types=1);

namespace App\Tests\Functional\Web;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Authorization\Role;
use App\Domain\Budget\MarginDriftThreshold;
use App\Domain\Margin\ProjectMargin;
use App\Domain\Project\ContractType;
use App\Domain\Project\Project;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Infrastructure\Persistence\Doctrine\DoctrineRoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\SodiumPasswordHasher;
use DateTimeImmutable;
use DateTimeZone;

/**
 * US-073 (T-073-06, CA-1/CA-3) — tableau de bord finance consolidé : accès réservé finance/direction
 * (403 sinon), CA consolidé pour VIEW_PROJECT_FINANCIALS, coût/marge/dérive en sus pour
 * VIEW_COLLABORATOR_COST (HAB-1), ventilation par client et par projet.
 */
final class FinanceDashboardTest extends WebTestCase
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
            $this->em->getClassMetadata(Project::class),
            $this->em->getClassMetadata(ProjectMargin::class),
            $this->em->getClassMetadata(MarginDriftThreshold::class),
        ];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        $this->tenant = TenantId::generate();
        new InitializeDefaultRoles(new DoctrineRoleRepository($this->em))->forTenant($this->tenant);

        $hasher = new SodiumPasswordHasher();
        $this->em->persist(new Tenant($this->tenant, 'Agence A'));
        $this->em->persist(new User($this->tenant, 'dg@agence.test', $hasher->hash(self::PASSWORD), ['Dirigeant']));
        $this->em->persist(new User($this->tenant, 'marc@agence.test', $hasher->hash(self::PASSWORD), ['Chef de projet']));
        $this->em->persist(new User($this->tenant, 'camille@agence.test', $hasher->hash(self::PASSWORD), ['Collaborateur']));

        $a = Project::createBusiness($this->tenant, 'PRJ-A', 'Projet A', 'ACME', '018f9c4e-0000-7000-8000-0000000000d1', 40_000_00, ContractType::FORFAIT, null, null, 60_000_00);
        $b = Project::createBusiness($this->tenant, 'PRJ-B', 'Projet B', 'Globex', '018f9c4e-0000-7000-8000-0000000000d2', 15_000_00, ContractType::REGIE, null, null);
        $this->em->persist($a);
        $this->em->persist($b);

        $frozenAt = new DateTimeImmutable('2026-12-01 09:00:00', new DateTimeZone('UTC'));
        $this->em->persist(ProjectMargin::freeze($this->tenant, '2026-11', $a->id(), 'Projet A', 42_000_00, 33_000_00, 20, 0, $frozenAt));
        $this->em->persist(ProjectMargin::freeze($this->tenant, '2026-11', $b->id(), 'Projet B', 20_000_00, 12_000_00, 10, 0, $frozenAt));

        $this->em->flush();
    }

    protected function tearDown(): void
    {
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->em->close();
        parent::tearDown();
    }

    public function testExecutiveSeesConsolidatedCostMarginAndClients(): void
    {
        $this->login('dg@agence.test');

        $this->client->request('GET', '/finance');

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('tableau de bord finance consolidé', $content);
        self::assertStringContainsString('ACME', $content);
        self::assertStringContainsString('Globex', $content);
        self::assertStringContainsString('Coût chargé', $content);
        self::assertStringContainsString('62 000,00', $content); // CA total (42 000 + 20 000)
        self::assertStringContainsString('Projets en dérive', $content);
    }

    public function testProjectManagerSeesRevenueButNotCost(): void
    {
        $this->login('marc@agence.test');

        $this->client->request('GET', '/finance');

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('ACME', $content);
        self::assertStringContainsString('62 000,00', $content); // CA consolidé visible
        self::assertStringNotContainsString('Coût chargé', $content);
    }

    public function testCollaboratorIsForbidden(): void
    {
        $this->login('camille@agence.test');

        $this->client->request('GET', '/finance');

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
