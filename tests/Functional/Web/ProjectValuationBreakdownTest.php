<?php

declare(strict_types=1);

namespace App\Tests\Functional\Web;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Authorization\Role;
use App\Domain\Project\Project;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use App\Domain\Timesheet\TimeEntry;
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
 * US-060 (T-060-04/05) — le tableau de bord ventile la valorisation aboutie **par projet** :
 * CA reconnu pour les habilités `VIEW_PROJECT_FINANCIALS` ; coût chargé et marge en sus pour les
 * porteurs de `VIEW_COLLABORATOR_COST` (gating HAB-1 préservé). Le rattachement projet passe par le
 * join `time_entry_valuation ↔ time_entry` (le snapshot ne dénormalise pas `project_id`).
 */
final class ProjectValuationBreakdownTest extends WebTestCase
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
            $this->em->getClassMetadata(TimeEntry::class),
            $this->em->getClassMetadata(TimeEntryValuation::class),
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

        $vitrine = new Project($this->tenant, 'VIT', 'Site vitrine');
        $mobile = new Project($this->tenant, 'MOB', 'Refonte app');
        $this->em->persist($vitrine);
        $this->em->persist($mobile);

        // Chaîne réelle : imputation → snapshot de valorisation (référence l'id de l'imputation).
        $collab = '018f9c4e-0000-7000-8000-0000000000c1';
        $when = new DateTimeImmutable('2026-08-20 10:00:00', new DateTimeZone('UTC'));
        $rateDate = new DateTimeImmutable('2026-01-01 00:00:00', new DateTimeZone('UTC'));

        // Site vitrine : 2 imputations → CA 1 560,00 € / coût 900,00 € / marge 660,00 €.
        $this->valuate($vitrine, $collab, '2026-08-17', 78000, 45000, $rateDate, $when);
        $this->valuate($vitrine, $collab, '2026-08-18', 78000, 45000, $rateDate, $when);
        // Refonte app : 1 imputation → CA 390,00 € / coût 225,00 € / marge 165,00 €.
        $this->valuate($mobile, $collab, '2026-08-19', 39000, 22500, $rateDate, $when);

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

    public function testProjectManagerSeesRevenuePerProjectWithoutCost(): void
    {
        $this->login('marc@agence.test');

        $this->client->request('GET', '/valorisation');

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Ventilation par projet', $content);
        self::assertStringContainsString('Site vitrine', $content);
        self::assertStringContainsString('Refonte app', $content);
        self::assertStringContainsString('1 560,00', $content); // CA Site vitrine
        self::assertStringContainsString('390,00', $content);   // CA Refonte app
        // Colonnes coût/marge masquées pour le chef de projet.
        self::assertStringNotContainsString('Coût chargé', $content);
    }

    public function testExecutiveSeesCostAndMarginPerProject(): void
    {
        $this->login('dg@agence.test');

        $this->client->request('GET', '/valorisation');

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Ventilation par projet', $content);
        self::assertStringContainsString('Coût chargé', $content);
        self::assertStringContainsString('900,00', $content); // coût Site vitrine (45000×2)
        self::assertStringContainsString('660,00', $content); // marge Site vitrine
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
