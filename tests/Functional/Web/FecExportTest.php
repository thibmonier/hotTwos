<?php

declare(strict_types=1);

namespace App\Tests\Functional\Web;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Authorization\Role;
use App\Domain\Fec\FecConfiguration;
use App\Domain\Margin\ProjectMargin;
use App\Domain\Period\AccountingPeriod;
use App\Domain\Period\PeriodStatus;
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
 * US-074 (T-074-06/07, CA-1/CA-3/CA-4) — téléchargement de l'export FEC : réservé finance/direction
 * (coût), fichier normé pour une période clôturée, refus si période ouverte.
 */
final class FecExportTest extends WebTestCase
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
            $this->em->getClassMetadata(AccountingPeriod::class),
            $this->em->getClassMetadata(FecConfiguration::class),
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

        // Période clôturée + config comptable + une marge figée.
        $this->em->persist(new AccountingPeriod($this->tenant, '2026-11', PeriodStatus::CLOSED));
        $this->em->persist(new FecConfiguration(
            $this->tenant,
            '123456789',
            'VT',
            'Ventes',
            '706000',
            'Prestations',
            '411000',
            'Clients',
            '641000',
            'Rémunérations',
            '791000',
            'Transferts de charges',
        ));
        $project = Project::createBusiness($this->tenant, 'PRJ-A', 'Site vitrine', 'ACME', '018f9c4e-0000-7000-8000-0000000000d1', 40_000_00, ContractType::FORFAIT, null, null);
        $this->em->persist($project);
        $this->em->persist(ProjectMargin::freeze($this->tenant, '2026-11', $project->id(), 'Site vitrine', 10_000_00, 5_800_00, 10, 0, new DateTimeImmutable('2026-12-01 09:00:00', new DateTimeZone('UTC'))));

        $this->em->flush();
    }

    protected function tearDown(): void
    {
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->em->close();
        parent::tearDown();
    }

    public function testExecutiveDownloadsFecFile(): void
    {
        $this->login('dg@agence.test');

        $this->client->request('GET', '/finance/export/fec?period=2026-11');

        self::assertResponseIsSuccessful();
        $disposition = (string) $this->client->getResponse()->headers->get('Content-Disposition');
        self::assertStringContainsString('123456789FEC20261130.txt', $disposition);
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('JournalCode', $content);
        self::assertStringContainsString('706000', $content); // compte produit
        self::assertStringContainsString('641000', $content); // compte charge
    }

    public function testProjectManagerWithoutCostIsForbidden(): void
    {
        $this->login('marc@agence.test');

        $this->client->request('GET', '/finance/export/fec?period=2026-11');

        self::assertResponseStatusCodeSame(403);
    }

    public function testOpenPeriodIsRefused(): void
    {
        $this->login('dg@agence.test');

        // 2026-10 n'est pas clôturée (aucune AccountingPeriod) → refus + redirection.
        $this->client->request('GET', '/finance/export/fec?period=2026-10');

        self::assertResponseRedirects();
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
