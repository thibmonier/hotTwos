<?php

declare(strict_types=1);

namespace App\Tests\Functional\Web;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Project\Project;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Infrastructure\Persistence\Doctrine\DoctrineRoleRepository;
use App\Tests\Support\Schema\ProvisionsFullSchema;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\SodiumPasswordHasher;

/**
 * US-099 (DSH-PRJ) — tableau de bord projets : KPI + projets en dérive (agrégation du suivi budgétaire),
 * réservé à VIEW_PROJECT_FINANCIALS (403 sinon).
 */
final class ProjectDashboardTest extends WebTestCase
{
    use ProvisionsFullSchema;

    private const string PASSWORD = 'motdepasse-solide';

    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private TenantId $tenant;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        $this->provisionSchema($this->em);

        $this->tenant = TenantId::generate();
        new InitializeDefaultRoles(new DoctrineRoleRepository($this->em))->forTenant($this->tenant);

        $hasher = new SodiumPasswordHasher();
        $this->em->persist(new Tenant($this->tenant, 'Agence A'));
        // Chef de projet : VIEW_PROJECT + VIEW_PROJECT_FINANCIALS. Collaborateur : ni financials.
        $this->em->persist(new User($this->tenant, 'marc@agence.test', $hasher->hash(self::PASSWORD), ['Chef de projet']));
        $this->em->persist(new User($this->tenant, 'camille@agence.test', $hasher->hash(self::PASSWORD), ['Collaborateur']));
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        $this->dropSchema($this->em);
        $this->em->close();
        parent::tearDown();
    }

    public function testManagerSeesDashboardKpisAndEmptyDriftState(): void
    {
        $marc = $this->em->getRepository(User::class)->findOneBy(['email' => 'marc@agence.test']);
        self::assertInstanceOf(User::class, $marc);
        $this->em->persist(new Project($this->tenant, 'PRJ-1', 'Refonte SI', true, $marc->id()));
        $this->em->flush();

        $this->login('marc@agence.test');
        $this->client->request('GET', '/projets/tableau-de-bord');

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Tableau de bord projets', $content);
        self::assertStringContainsString('Projets suivis', $content);       // KPI (CA-1)
        self::assertStringContainsString('En dérive', $content);
        self::assertStringContainsString('Aucun projet en dérive', $content); // état vide (CA-2)
    }

    public function testCollaboratorIsForbidden(): void
    {
        // CA-3 : sans VIEW_PROJECT_FINANCIALS, accès refusé.
        $this->login('camille@agence.test');
        $this->client->request('GET', '/projets/tableau-de-bord', server: ['HTTP_ACCEPT' => 'text/html']);

        self::assertResponseStatusCodeSame(403);
    }

    private function login(string $email): void
    {
        $this->client->request('POST', '/api/login', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['email' => $email, 'password' => self::PASSWORD], JSON_THROW_ON_ERROR));
        self::assertResponseIsSuccessful();
    }
}
