<?php

declare(strict_types=1);

namespace App\Tests\Functional\Web;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Calendar\ClosurePeriod;
use App\Domain\Calendar\Holiday;
use App\Domain\Absence\AbsenceRequest;
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
 * US-060 (T-060-03/05) — le tableau de bord affiche l'occupation par collaborateur sur le mois de
 * la dernière prestation valorisée : jours valorisés (join `time_entry_valuation ↔ time_entry`) sur
 * capacité (jours ouvrés − absences). Habilité `VIEW_PROJECT_FINANCIALS`.
 */
final class OccupationDashboardTest extends WebTestCase
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
            $this->em->getClassMetadata(Holiday::class),
            $this->em->getClassMetadata(ClosurePeriod::class),
            $this->em->getClassMetadata(User::class),
            $this->em->getClassMetadata(Role::class),
            $this->em->getClassMetadata(Project::class),
            $this->em->getClassMetadata(TimeEntry::class),
            $this->em->getClassMetadata(TimeEntryValuation::class),
            $this->em->getClassMetadata(AbsenceRequest::class),
        ];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        $this->tenant = TenantId::generate();
        new InitializeDefaultRoles(new DoctrineRoleRepository($this->em))->forTenant($this->tenant);

        $hasher = new SodiumPasswordHasher();
        $this->em->persist(new Tenant($this->tenant, 'Agence A'));
        $this->em->persist(new User($this->tenant, 'marc@agence.test', $hasher->hash(self::PASSWORD), ['Chef de projet']));

        $alice = new User($this->tenant, 'alice@agence.test', $hasher->hash(self::PASSWORD), ['Collaborateur']);
        $alice->rename('Alice', 'Durand');
        $this->em->persist($alice);

        $project = new Project($this->tenant, 'VIT', 'Site vitrine');
        $this->em->persist($project);

        // 3 jours ouvrés d'août valorisés pour Alice.
        $when = new DateTimeImmutable('2026-08-20 10:00:00', new DateTimeZone('UTC'));
        $rateDate = new DateTimeImmutable('2026-01-01 00:00:00', new DateTimeZone('UTC'));
        foreach (['2026-08-03', '2026-08-04', '2026-08-05'] as $day) {
            $entry = new TimeEntry($this->tenant, $alice->id(), $project->id(), new DateTimeImmutable($day), 420);
            $this->em->persist($entry);
            $this->em->persist(TimeEntryValuation::valued($this->tenant, $entry->id(), 45000, 78000, 45000, 78000, $rateDate, $when));
        }

        $this->em->flush();
    }

    protected function tearDown(): void
    {
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->em->close();
        parent::tearDown();
    }

    public function testDashboardShowsOccupationPerCollaborator(): void
    {
        $this->login('marc@agence.test');

        $this->client->request('GET', '/valorisation');

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Occupation par collaborateur', $content);
        self::assertStringContainsString('2026-08', $content);
        self::assertStringContainsString('Alice Durand', $content);
        // 3 jours valorisés / 21 jours ouvrés d'août 2026 = 14 %.
        self::assertStringContainsString('14 %', $content);
    }

    public function testBillableOccupationExcludesInternalProjects(): void
    {
        // Bob : 2 jours sur projet facturable + 2 jours sur projet interne (août 2026).
        $bob = new User($this->tenant, 'bob@agence.test', new SodiumPasswordHasher()->hash(self::PASSWORD), ['Collaborateur']);
        $bob->rename('Bob', 'Martin');
        $this->em->persist($bob);

        $billable = new Project($this->tenant, 'APP', 'Application');
        $this->em->persist($billable);
        $internal = new Project($this->tenant, 'RND', 'R&D interne');
        $internal->markInternal(true);
        $this->em->persist($internal);

        $when = new DateTimeImmutable('2026-08-20 10:00:00', new DateTimeZone('UTC'));
        $rateDate = new DateTimeImmutable('2026-01-01 00:00:00', new DateTimeZone('UTC'));
        $plan = [['2026-08-06', $billable], ['2026-08-07', $billable], ['2026-08-10', $internal], ['2026-08-11', $internal]];
        foreach ($plan as [$day, $project]) {
            $entry = new TimeEntry($this->tenant, $bob->id(), $project->id(), new DateTimeImmutable($day), 420);
            $this->em->persist($entry);
            $this->em->persist(TimeEntryValuation::valued($this->tenant, $entry->id(), 45000, 78000, 45000, 78000, $rateDate, $when));
        }
        $this->em->flush();

        $this->login('marc@agence.test');
        $this->client->request('GET', '/valorisation');
        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();

        self::assertStringContainsString('Bob Martin', $content);
        self::assertStringContainsString('Facturable', $content);        // colonne présente
        // Bob : 4 jours valorisés / 21 = 19 % ; facturable 2 / 21 = 10 % (projet interne exclu).
        self::assertStringContainsString('19 %', $content);
        self::assertStringContainsString('10 %', $content);
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
