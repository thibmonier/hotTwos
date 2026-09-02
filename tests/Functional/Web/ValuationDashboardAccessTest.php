<?php

declare(strict_types=1);

namespace App\Tests\Functional\Web;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Authorization\Role;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Infrastructure\Persistence\Doctrine\DoctrineRoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\SodiumPasswordHasher;

/**
 * Régression recette US-066 (REC-20260902-sprint7-ergo, ANO-1) : un refus d'habilitation sur une
 * **route web** ne doit PAS renvoyer un JSON brut ni divulguer le slug de permission interne
 * (règle 11 §7 — mishandling of exceptional conditions). L'utilisateur reçoit une page 403 habillée ;
 * un client API reçoit un JSON 403 **générique** (sans interne).
 *
 * @group regression
 */
final class ValuationDashboardAccessTest extends WebTestCase
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
        ];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        $tenant = TenantId::generate();
        new InitializeDefaultRoles(new DoctrineRoleRepository($this->em))->forTenant($tenant);

        // Une collaboratrice n'a pas VIEW_PROJECT_FINANCIALS → /valorisation lui est refusé.
        $hasher = new SodiumPasswordHasher();
        $this->em->persist(new Tenant($tenant, 'Agence A'));
        $this->em->persist(new User($tenant, 'camille@agence.test', $hasher->hash('motdepasse-solide'), ['Collaborateur']));
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->em->close();
        parent::tearDown();
    }

    public function testWebNavigationForbiddenRendersBrandedHtmlWithoutLeakingPermissionSlug(): void
    {
        $this->login('camille@agence.test');

        $this->client->request('GET', '/valorisation', server: ['HTTP_ACCEPT' => 'text/html']);

        self::assertResponseStatusCodeSame(403);
        self::assertResponseHeaderSame('Content-Type', 'text/html; charset=UTF-8');
        $body = (string) $this->client->getResponse()->getContent();
        self::assertStringNotContainsString('view:project_financials', $body, 'Le slug de permission interne ne doit pas fuiter.');
        self::assertStringNotContainsString('{"error"', $body, 'La navigation web ne doit pas recevoir de JSON brut.');
        self::assertStringContainsString('Accès refusé', $body, 'Page 403 habillée attendue.');
    }

    public function testApiRequestForbiddenReturnsGenericJsonWithoutLeakingPermissionSlug(): void
    {
        $this->login('camille@agence.test');

        $this->client->request('GET', '/valorisation', server: ['HTTP_ACCEPT' => 'application/json']);

        self::assertResponseStatusCodeSame(403);
        self::assertResponseHeaderSame('Content-Type', 'application/json');
        /** @var array{error?: string} $payload */
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('error', $payload);
        self::assertStringNotContainsString('view:project_financials', $payload['error'] ?? '');
        self::assertStringNotContainsString('project_financials', $payload['error'] ?? '');
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
