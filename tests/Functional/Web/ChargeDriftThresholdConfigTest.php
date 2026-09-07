<?php

declare(strict_types=1);

namespace App\Tests\Functional\Web;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Authorization\Role;
use App\Domain\Budget\ChargeDriftThreshold;
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
 * US-079b (EF-PRJ-15, CA-3/CA-4, HAB-1) — configuration du seuil de dérive de charge par type de projet
 * (alerte + escalade direction), gating MANAGE_ORGANIZATION, et prise en compte sur la fiche projet.
 */
final class ChargeDriftThresholdConfigTest extends WebTestCase
{
    private const string PASSWORD = 'motdepasse-solide';
    private const string RESPONSIBLE = '018f9c4e-0000-7000-8000-0000000000c1';

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
        $this->em->persist(new User($this->tenant, 'admin@agence.test', $hasher->hash(self::PASSWORD), ['Administrateur']));
        $this->em->persist(new User($this->tenant, 'dg@agence.test', $hasher->hash(self::PASSWORD), ['Dirigeant']));

        $project = Project::createBusiness($this->tenant, 'PRJ-0001', 'Pilotage', 'ACME', self::RESPONSIBLE, 100_000_00, ContractType::FORFAIT, null, null, 120_000_00);
        $this->em->persist($project);
        $this->projectId = $project->id();

        $this->em->flush();
    }

    protected function tearDown(): void
    {
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->em->close();
        parent::tearDown();
    }

    public function testAdminConfiguresPerTypeThresholds(): void
    {
        $this->login('admin@agence.test');

        $crawler = $this->client->request('GET', '/finance/config-derive-charge');
        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Forfait', $content);
        self::assertStringContainsString('Régie', $content);
        $token = $crawler->filter('input[name="_token"]')->attr('value') ?? '';

        $this->client->request('POST', '/finance/config-derive-charge', [
            '_token' => $token,
            'alert_forfait' => '8', 'escalation_forfait' => '15',
            'alert_regie' => '15', 'escalation_regie' => '25',
        ]);
        self::assertResponseRedirects();

        $saved = (string) $this->client->request('GET', '/finance/config-derive-charge')->filter('#alert-forfait')->attr('value');
        self::assertSame('8', $saved);
    }

    public function testRejectsEscalationBelowAlert(): void
    {
        $this->login('admin@agence.test');
        $crawler = $this->client->request('GET', '/finance/config-derive-charge');
        $token = $crawler->filter('input[name="_token"]')->attr('value') ?? '';

        // Escalade (5) < alerte (10) → refus métier, aucun enregistrement.
        $this->client->request('POST', '/finance/config-derive-charge', [
            '_token' => $token,
            'alert_forfait' => '10', 'escalation_forfait' => '5',
            'alert_regie' => '15', 'escalation_regie' => '25',
        ]);
        self::assertResponseRedirects();
        $value = (string) $this->client->request('GET', '/finance/config-derive-charge')->filter('#alert-forfait')->attr('value');
        self::assertSame((string) 10.0, $value); // repli défaut (aucun seuil forfait enregistré)
    }

    public function testProjectManagerCannotAccessConfiguration(): void
    {
        $this->login('dg@agence.test'); // Dirigeant : pas de MANAGE_ORGANIZATION
        $this->client->request('GET', '/finance/config-derive-charge');
        self::assertResponseStatusCodeSame(403);
    }

    public function testPilotageReflectsPerTypeEscalation(): void
    {
        // Seuils forfait bas : alerte 5 %, escalade 10 %.
        $this->em->persist(new ChargeDriftThreshold($this->tenant, ContractType::FORFAIT, 5.0, 10.0));
        // Budget 100 000 € ; consommé 24 000 € à 20 % → atterrissage 120 000 € (+20 %) → escalade (> 10 %).
        $lot = new ProjectLot($this->tenant, $this->projectId, 'Développement', 10, 8_000_000);
        $lot->recordProgress(20, null);
        $this->em->persist($lot);
        $rateDate = new DateTimeImmutable('2026-01-01 00:00:00', new DateTimeZone('UTC'));
        $when = new DateTimeImmutable('2026-08-20 10:00:00', new DateTimeZone('UTC'));
        $entry = new TimeEntry($this->tenant, self::RESPONSIBLE, $this->projectId, new DateTimeImmutable('2026-08-18'), 420);
        $this->em->persist($entry);
        $this->em->persist(TimeEntryValuation::valued($this->tenant, $entry->id(), 24_000_00, 6_000_00, 24_000_00, 6_000_00, $rateDate, $when));
        $this->em->flush();

        $this->login('dg@agence.test');
        $this->client->request('GET', '/projets/'.$this->projectId);
        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Escalade direction', $content);
        self::assertStringContainsString('Dérive de charge précoce', $content);
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
