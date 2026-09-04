<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Authorization\Role;
use App\Domain\Project\ExceptionalImputationOpening;
use App\Domain\Project\ExternalCommitment;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectReopening;
use App\Domain\Project\ProjectAssignment;
use App\Domain\Project\ProjectLot;
use App\Domain\Project\ProjectMilestone;
use App\Domain\Project\ProjectStatus;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Infrastructure\Persistence\Doctrine\DoctrineProjectRepository;
use App\Infrastructure\Persistence\Doctrine\DoctrineRoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\SodiumPasswordHasher;

/**
 * US-030 (T-030-07) — écran projets : liste (VIEW_PROJECT), création réservée (CREATE_PROJECT) avec
 * RG-PRJ-1, cycle de vie (transition valide/invalide), PRG + code séquentiel. 401 anonyme.
 */
final class ProjectPageTest extends WebTestCase
{
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
            $this->em->getClassMetadata(ProjectLot::class),
            $this->em->getClassMetadata(ProjectMilestone::class),
            $this->em->getClassMetadata(ProjectAssignment::class),
            $this->em->getClassMetadata(ExceptionalImputationOpening::class),
            $this->em->getClassMetadata(ExternalCommitment::class),
            $this->em->getClassMetadata(ProjectReopening::class),
        ];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        $this->tenant = TenantId::generate();
        new InitializeDefaultRoles(new DoctrineRoleRepository($this->em))->forTenant($this->tenant);

        $hasher = new SodiumPasswordHasher();
        $this->em->persist(new Tenant($this->tenant, 'Agence A'));
        $this->em->persist(new User($this->tenant, 'camille@agence.test', $hasher->hash('motdepasse-solide'), ['Collaborateur']));
        $this->em->persist(new User($this->tenant, 'marc@agence.test', $hasher->hash('motdepasse-solide'), ['Chef de projet']));
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
        $this->client->request('GET', '/projets');
        // US-068 : route web → redirection vers la page de connexion (plus de 401 web).
        self::assertResponseRedirects('/login');
    }

    public function testCollaboratorSeesListWithoutCreate(): void
    {
        $this->login('camille@agence.test');
        $this->client->request('GET', '/projets');

        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('Nouveau projet', $this->client->getResponse()->getContent() ?: '');
    }

    public function testManagerCreatesProjectWithSequentialCode(): void
    {
        $this->login('marc@agence.test');
        $crawler = $this->client->request('GET', '/projets/nouveau');
        self::assertResponseIsSuccessful();

        $token = $crawler->filter('input[name="_token"]')->attr('value') ?? '';
        $this->client->request('POST', '/projets', [
            '_token' => $token,
            'name' => 'Refonte SI',
            'clientName' => 'Acme Corp',
            'budgetEuros' => '120000',
            'contractType' => 'forfait',
        ]);

        self::assertResponseRedirects();
        $projects = new DoctrineProjectRepository($this->em)->findAllByTenant($this->tenant);
        self::assertCount(1, $projects);
        self::assertSame('PRJ-0001', $projects[0]->code());
        self::assertSame(ProjectStatus::EN_PREPARATION, $projects[0]->status());
    }

    public function testCreationWithoutBudgetIsRejected(): void
    {
        $this->login('marc@agence.test');
        $crawler = $this->client->request('GET', '/projets/nouveau');
        $token = $crawler->filter('input[name="_token"]')->attr('value') ?? '';

        $this->client->request('POST', '/projets', [
            '_token' => $token,
            'name' => 'Sans budget',
            'clientName' => 'Acme',
            'contractType' => 'forfait',
        ]);

        self::assertResponseRedirects('/projets/nouveau');
        self::assertCount(0, new DoctrineProjectRepository($this->em)->findAllByTenant($this->tenant));
    }

    public function testLifecycleTransition(): void
    {
        $this->login('marc@agence.test');
        $create = $this->client->request('GET', '/projets/nouveau');
        $token = $create->filter('input[name="_token"]')->attr('value') ?? '';
        $this->client->request('POST', '/projets', [
            '_token' => $token, 'name' => 'Refonte', 'clientName' => 'Acme', 'budgetEuros' => '50000', 'contractType' => 'regie',
        ]);
        $id = new DoctrineProjectRepository($this->em)->findAllByTenant($this->tenant)[0]->id();
        $this->em->clear();

        $show = $this->client->request('GET', '/projets/'.$id);
        self::assertResponseIsSuccessful();
        $statusToken = $show->filter('input[name="_token"]')->first()->attr('value') ?? '';

        $this->client->request('POST', '/projets/'.$id.'/statut', [
            '_token' => $statusToken, 'status' => 'en_cours',
        ]);
        self::assertResponseRedirects('/projets/'.$id);

        self::assertSame(ProjectStatus::EN_COURS, new DoctrineProjectRepository($this->em)->find($this->tenant, $id)?->status());
    }

    private function login(string $email): void
    {
        $this->client->request('POST', '/api/login', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['email' => $email, 'password' => 'motdepasse-solide'], JSON_THROW_ON_ERROR));
        self::assertResponseIsSuccessful();
    }
}
