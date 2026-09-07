<?php

declare(strict_types=1);

namespace App\Tests\Functional\Web;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Authorization\Role;
use App\Domain\Budget\ChargeDriftThreshold;
use App\Domain\Budget\ChargeLanding;
use App\Domain\Budget\ChargeLandingSnapshot;
use App\Domain\Budget\MarginDriftThreshold;
use App\Domain\Client\Client;
use App\Domain\Invoice\Invoice;
use App\Domain\Pricing\Profile;
use App\Domain\Project\BudgetAmendment;
use App\Domain\Project\ContractType;
use App\Domain\Project\ExceptionalImputationOpening;
use App\Domain\Project\ExternalCommitment;
use App\Domain\Project\LotProfileBudget;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectAssignment;
use App\Domain\Project\ProjectLot;
use App\Domain\Project\ProjectMilestone;
use App\Domain\Project\ProjectReopening;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use App\Domain\Timesheet\TimeEntry;
use App\Domain\User\User;
use App\Domain\Valuation\TimeEntryValuation;
use App\Infrastructure\Persistence\Doctrine\DoctrineRoleRepository;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\SodiumPasswordHasher;

/**
 * US-079c (EF-PRJ-16, CA-2) — la fiche projet expose la « Courbe d'atterrissage » : la série des
 * snapshots par période (évolution du dépassement projeté), avec gating HAB-1 sur les montants de coût.
 */
final class ChargeLandingCurveTest extends WebTestCase
{
    private const string PASSWORD = 'motdepasse-solide';

    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private TenantId $tenant;
    private string $projectId;

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
            $this->em->getClassMetadata(ProjectLot::class),
            $this->em->getClassMetadata(ProjectMilestone::class),
            $this->em->getClassMetadata(ProjectAssignment::class),
            $this->em->getClassMetadata(ExceptionalImputationOpening::class),
            $this->em->getClassMetadata(ExternalCommitment::class),
            $this->em->getClassMetadata(ProjectReopening::class),
            $this->em->getClassMetadata(TimeEntry::class),
            $this->em->getClassMetadata(TimeEntryValuation::class),
            $this->em->getClassMetadata(MarginDriftThreshold::class),
            $this->em->getClassMetadata(ChargeDriftThreshold::class),

            $this->em->getClassMetadata(ChargeLandingSnapshot::class),
            $this->em->getClassMetadata(Client::class),
            $this->em->getClassMetadata(Invoice::class),
            $this->em->getClassMetadata(BudgetAmendment::class),
            $this->em->getClassMetadata(Profile::class),
            $this->em->getClassMetadata(LotProfileBudget::class),
        ];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        $this->tenant = TenantId::generate();
        new InitializeDefaultRoles(new DoctrineRoleRepository($this->em))->forTenant($this->tenant);

        $hasher = new SodiumPasswordHasher();
        $this->em->persist(new Tenant($this->tenant, 'Agence A'));
        $this->em->persist(new User($this->tenant, 'marc@agence.test', $hasher->hash(self::PASSWORD), ['Chef de projet']));
        $this->em->persist(new User($this->tenant, 'dg@agence.test', $hasher->hash(self::PASSWORD), ['Dirigeant']));

        $project = Project::createBusiness($this->tenant, 'PRJ-0001', 'Pilotage', 'ACME', '018f9c4e-0000-7000-8000-0000000000c1', 100_000_00, ContractType::FORFAIT, null, null, 120_000_00);
        $this->em->persist($project);
        $this->projectId = $project->id();

        $capturedAt = new DateTimeImmutable('2026-08-31 23:00:00', new DateTimeZone('UTC'));
        // Juillet : dérive précoce sans escalade ; Août : franchissement du 2e seuil (escalade).
        $this->em->persist(ChargeLandingSnapshot::capture($this->tenant, '2026-07', $this->projectId, 'Pilotage', new ChargeLanding(true, 115_000_00, 100_000_00, 15.0, 30.0, 30, true, false), $capturedAt));
        $this->em->persist(ChargeLandingSnapshot::capture($this->tenant, '2026-08', $this->projectId, 'Pilotage', new ChargeLanding(true, 150_000_00, 100_000_00, 50.0, 15.0, 10, true, true), $capturedAt));

        $this->em->flush();
    }

    protected function tearDown(): void
    {
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->em->close();
        parent::tearDown();
    }

    public function testExecutiveSeesCurveWithLandingAmounts(): void
    {
        $this->login('dg@agence.test');
        $this->client->request('GET', '/projets/'.$this->projectId);

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Courbe d\'atterrissage', $content);
        self::assertStringContainsString('2026-07', $content);
        self::assertStringContainsString('2026-08', $content);
        self::assertStringContainsString('150 000', $content); // atterrissage € visible (coût)
        self::assertStringContainsString('Dérive précoce', $content);
        self::assertStringContainsString('Escalade direction', $content); // 2e seuil franchi (US-079b) mis en évidence
    }

    public function testProjectManagerSeesCurveWithoutLandingAmounts(): void
    {
        $this->login('marc@agence.test');
        $this->client->request('GET', '/projets/'.$this->projectId);

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Courbe d\'atterrissage', $content);
        self::assertStringContainsString('2026-08', $content);
        self::assertStringNotContainsString('150 000', $content); // montant de coût masqué (HAB-1)
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
