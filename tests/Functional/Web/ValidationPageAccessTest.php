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
use App\Infrastructure\Persistence\Doctrine\DoctrineRoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\SodiumPasswordHasher;

/**
 * Régression recette US-066 (REC-20260902-sprint7-ergo, V1) : la page web /validation (US-055)
 * doit exiger l'habilitation VALIDATE_TIME, en cohérence avec la nav (filtrée `validate:time`) et
 * avec /valorisation. Sinon un collaborateur atteint un écran « Ma responsabilité » vide et trompeur
 * (deny-by-default, règle 11). La mutation POST /api/time-entries/validate était déjà gardée.
 *
 * @group regression
 */
final class ValidationPageAccessTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

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
        ];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        $tenant = TenantId::generate();
        new InitializeDefaultRoles(new DoctrineRoleRepository($this->em))->forTenant($tenant);

        $hasher = new SodiumPasswordHasher();
        $this->em->persist(new Tenant($tenant, 'Agence A'));
        $this->em->persist(new User($tenant, 'camille@agence.test', $hasher->hash('motdepasse-solide'), ['Collaborateur']));
        $this->em->persist(new User($tenant, 'marc@agence.test', $hasher->hash('motdepasse-solide'), ['Chef de projet']));
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->em->close();
        parent::tearDown();
    }

    public function testCollaboratorWithoutValidatePermissionIsForbidden(): void
    {
        $this->login('camille@agence.test');

        $this->client->request('GET', '/validation', server: ['HTTP_ACCEPT' => 'text/html']);

        self::assertResponseStatusCodeSame(403);
        $body = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Accès refusé', $body);
        self::assertStringNotContainsString('validate:time', $body);
    }

    public function testProjectManagerCanAccessValidationPage(): void
    {
        $this->login('marc@agence.test');

        $this->client->request('GET', '/validation', server: ['HTTP_ACCEPT' => 'text/html']);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('responsabilité', (string) $this->client->getResponse()->getContent());
    }

    private function login(string $email): void
    {
        $this->client->request(
            'POST',
            '/api/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => $email, 'password' => 'motdepasse-solide'], JSON_THROW_ON_ERROR),
        );
        self::assertResponseIsSuccessful();
    }
}
