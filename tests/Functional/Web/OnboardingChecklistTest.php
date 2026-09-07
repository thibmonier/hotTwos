<?php

declare(strict_types=1);

namespace App\Tests\Functional\Web;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Authorization\Role;
use App\Domain\Pricing\CalculationMode;
use App\Domain\Pricing\Profile;
use App\Domain\Project\Project;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use App\Domain\Timesheet\TimeEntry;
use App\Domain\User\User;
use App\Infrastructure\Persistence\Doctrine\DoctrineRoleRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\SodiumPasswordHasher;

/**
 * US-019 (EF-REF-29, CA-1/CA-3) — la checklist de mise en route s'affiche pour un administrateur tant
 * qu'elle n'est pas complète, et disparaît une fois toutes les étapes accomplies.
 */
final class OnboardingChecklistTest extends WebTestCase
{
    private const string PASSWORD = 'motdepasse-solide';
    private const string RESP = '018f9c4e-0000-7000-8000-0000000000c1';

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
            $this->em->getClassMetadata(Profile::class),
            $this->em->getClassMetadata(Project::class),
            $this->em->getClassMetadata(TimeEntry::class),
        ];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        $this->tenant = TenantId::generate();
        new InitializeDefaultRoles(new DoctrineRoleRepository($this->em))->forTenant($this->tenant);

        $hasher = new SodiumPasswordHasher();
        $this->em->persist(new Tenant($this->tenant, 'Agence A'));
        $this->em->persist(new User($this->tenant, 'admin@agence.test', $hasher->hash(self::PASSWORD), ['Administrateur']));
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->em->close();
        parent::tearDown();
    }

    public function testChecklistShownWhenIncomplete(): void
    {
        $this->login('admin@agence.test');
        $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Mise en route', $content);
        self::assertStringContainsString('Créer un premier projet', $content);
    }

    public function testChecklistHiddenWhenComplete(): void
    {
        // Profil + projet + un temps saisi → checklist complète, masquée.
        $this->em->persist(new Profile($this->tenant, 'Consultant', CalculationMode::LOADED));
        $project = new Project($this->tenant, 'ALPHA', 'Projet Alpha', true, self::RESP);
        $this->em->persist($project);
        $this->em->persist(new TimeEntry($this->tenant, self::RESP, $project->id(), new DateTimeImmutable('2027-03-01'), 420));
        $this->em->flush();

        $this->login('admin@agence.test');
        $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('Mise en route', (string) $this->client->getResponse()->getContent());
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
