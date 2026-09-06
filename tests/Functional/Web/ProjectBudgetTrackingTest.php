<?php

declare(strict_types=1);

namespace App\Tests\Functional\Web;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Authorization\Role;
use App\Domain\Project\ContractType;
use App\Domain\Project\ExceptionalImputationOpening;
use App\Domain\Project\ExternalCommitment;
use App\Domain\Pricing\Profile;
use App\Domain\Project\BudgetAmendment;
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
use App\Domain\Client\Client;
use App\Domain\Invoice\Invoice;
use App\Domain\Budget\MarginDriftThreshold;
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
 * US-072 (T-072-03/04, CA-1/CA-2/CA-4, HAB-1) — la fiche projet expose le « Suivi budgétaire » :
 * CA cible/réalisé pour VIEW_PROJECT_FINANCIALS ; coût, consommation, marge et alerte de dérive en
 * sus pour VIEW_COLLABORATOR_COST ; message dédié pour un projet sans budget.
 */
final class ProjectBudgetTrackingTest extends WebTestCase
{
    private const string PASSWORD = 'motdepasse-solide';

    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private TenantId $tenant;
    private string $budgetedProjectId;
    private string $noBudgetProjectId;

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

        // Projet budgété : coût cible 40 000 € / CA cible 60 000 € (marge cible 33,33 %).
        $budgeted = Project::createBusiness(
            $this->tenant,
            'PRJ-0001',
            'Refonte app',
            'ACME',
            '018f9c4e-0000-7000-8000-0000000000c1',
            40_000_00,
            ContractType::FORFAIT,
            null,
            null,
            60_000_00,
        );
        $this->em->persist($budgeted);
        $this->budgetedProjectId = $budgeted->id();

        // Réalisé : coût 33 000 € / CA 42 000 € (taux 21,43 % → dérive 11,9 pts > seuil 5).
        $collab = '018f9c4e-0000-7000-8000-0000000000c1';
        $rateDate = new DateTimeImmutable('2026-01-01 00:00:00', new DateTimeZone('UTC'));
        $when = new DateTimeImmutable('2026-08-20 10:00:00', new DateTimeZone('UTC'));
        $this->valuate($budgeted, $collab, '2026-08-17', 21_000_00, 16_500_00, $rateDate, $when);
        $this->valuate($budgeted, $collab, '2026-08-18', 21_000_00, 16_500_00, $rateDate, $when);

        $noBudget = new Project($this->tenant, 'PRJ-0002', 'Projet interne');
        $this->em->persist($noBudget);
        $this->noBudgetProjectId = $noBudget->id();

        $this->em->flush();
    }

    protected function tearDown(): void
    {
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->em->close();
        parent::tearDown();
    }

    private function valuate(Project $project, string $userId, string $day, int $revenue, int $cost, DateTimeImmutable $rateDate, DateTimeImmutable $when): void
    {
        $entry = new TimeEntry($this->tenant, $userId, $project->id(), new DateTimeImmutable($day), 420);
        $this->em->persist($entry);
        $this->em->persist(TimeEntryValuation::valued($this->tenant, $entry->id(), $cost, $revenue, $cost, $revenue, $rateDate, $when));
    }

    public function testExecutiveSeesBudgetCostMarginAndDriftAlert(): void
    {
        $this->login('dg@agence.test');

        $this->client->request('GET', '/projets/'.$this->budgetedProjectId);

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Suivi budgétaire', $content);
        self::assertStringContainsString('Consommation budgétaire', $content);
        self::assertStringContainsString('60 000,00', $content); // CA cible
        self::assertStringContainsString('42 000,00', $content); // CA réalisé
        self::assertStringContainsString('33 000,00', $content); // coût réalisé
        self::assertStringContainsString('Dérive financière défavorable', $content);
    }

    public function testProjectManagerSeesRevenueButNotCostOrDrift(): void
    {
        $this->login('marc@agence.test');

        $this->client->request('GET', '/projets/'.$this->budgetedProjectId);

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Suivi budgétaire', $content);
        self::assertStringContainsString('42 000,00', $content); // CA réalisé visible
        // Coût, consommation et dérive masqués pour le chef de projet (HAB-1).
        self::assertStringNotContainsString('Consommation budgétaire', $content);
        self::assertStringNotContainsString('Dérive financière défavorable', $content);
    }

    public function testProjectWithoutBudgetShowsMessage(): void
    {
        $this->login('dg@agence.test');

        $this->client->request('GET', '/projets/'.$this->noBudgetProjectId);

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Suivi budgétaire', $content);
        self::assertStringContainsString('Aucun budget défini pour ce projet', $content);
    }

    public function testEarlyChargeDriftAlertOnProjectPage(): void
    {
        // Budget charge 100 000 € ; consommé 15 000 € à 10 % d'avancement → atterrissage 150 000 €
        // (+50 %), consommation 15 % (< 50 %) → dérive de charge précoce (US-036, OBJ-2).
        $project = Project::createBusiness(
            $this->tenant,
            'PRJ-0003',
            'Pilotage',
            'ACME',
            '018f9c4e-0000-7000-8000-0000000000c1',
            100_000_00,
            ContractType::FORFAIT,
            null,
            null,
            120_000_00,
        );
        $this->em->persist($project);
        $lot = new ProjectLot($this->tenant, $project->id(), 'Développement', 10, 8_000_000);
        $lot->recordProgress(10, null);
        $this->em->persist($lot);
        $this->valuate($project, '018f9c4e-0000-7000-8000-0000000000c1', '2026-08-19', 6_000_00, 15_000_00, new DateTimeImmutable('2026-01-01 00:00:00', new DateTimeZone('UTC')), new DateTimeImmutable('2026-08-20 10:00:00', new DateTimeZone('UTC')));
        $this->em->flush();

        // Dirigeant (coût visible) : alerte + montant d'atterrissage.
        $this->login('dg@agence.test');
        $this->client->request('GET', '/projets/'.$project->id());
        self::assertResponseIsSuccessful();
        $dg = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Dérive de charge précoce', $dg);
        self::assertStringContainsString('150 000', $dg); // atterrissage € visible

        // Chef de projet (sans coût) : alerte visible, montant € masqué (HAB-1).
        $this->login('marc@agence.test');
        $this->client->request('GET', '/projets/'.$project->id());
        $marc = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Dérive de charge précoce', $marc);
        self::assertStringNotContainsString('150 000', $marc);
    }

    public function testBudgetAmendmentUpdatesCurrentBudget(): void
    {
        // Projet budgété coût 40 000 € ; un avenant de +15 000 € → budget courant 55 000 € (US-033).
        $this->login('marc@agence.test');
        $show = $this->client->request('GET', '/projets/'.$this->budgetedProjectId);
        self::assertResponseIsSuccessful();
        $token = $show->filter('form[action="/projets/'.$this->budgetedProjectId.'/avenant"] input[name="_token"]')->attr('value') ?? '';

        $this->client->request('POST', '/projets/'.$this->budgetedProjectId.'/avenant', [
            '_token' => $token, 'deltaCostEuros' => '15000', 'deltaRevenueEuros' => '0', 'reason' => 'périmètre étendu — lot 3',
        ]);
        self::assertResponseRedirects();

        $content = $this->client->request('GET', '/projets/'.$this->budgetedProjectId)->filter('#panel-budget')->html();
        self::assertStringContainsString('55 000', $content);                  // budget de charge courant (40 000 + 15 000)
        self::assertStringContainsString('périmètre étendu — lot 3', $content); // historique de l'avenant
    }

    public function testAmendmentRequiresReason(): void
    {
        $this->login('marc@agence.test');
        $show = $this->client->request('GET', '/projets/'.$this->budgetedProjectId);
        $token = $show->filter('form[action="/projets/'.$this->budgetedProjectId.'/avenant"] input[name="_token"]')->attr('value') ?? '';

        $this->client->request('POST', '/projets/'.$this->budgetedProjectId.'/avenant', [
            '_token' => $token, 'deltaCostEuros' => '20000', 'deltaRevenueEuros' => '0', 'reason' => '',
        ]);
        self::assertResponseRedirects();
        // Aucun avenant enregistré : budget courant inchangé (pas de « 1 avenant »).
        $content = $this->client->request('GET', '/projets/'.$this->budgetedProjectId)->filter('#panel-budget')->html();
        self::assertStringNotContainsString('avenant(s)', $content);
    }

    public function testPilotageCsvExportGatesCostColumns(): void
    {
        // Dirigeant (coût visible) : les colonnes coût sont remplies.
        $this->login('dg@agence.test');
        $this->client->request('GET', '/projets/'.$this->budgetedProjectId.'/pilotage/export');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('text/csv', (string) $this->client->getResponse()->headers->get('Content-Type'));
        $dg = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Atterrissage (€)', $dg);           // en-tête
        self::assertStringContainsString('33000.00', $dg);                   // consommé visible (coût)

        // Chef de projet (sans coût) : colonnes coût vides (HAB-1).
        $this->login('marc@agence.test');
        $this->client->request('GET', '/projets/'.$this->budgetedProjectId.'/pilotage/export');
        self::assertResponseIsSuccessful();
        $marc = (string) $this->client->getResponse()->getContent();
        self::assertStringNotContainsString('33000.00', $marc);              // coût masqué
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
