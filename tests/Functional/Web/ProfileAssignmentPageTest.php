<?php

declare(strict_types=1);

namespace App\Tests\Functional\Web;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Authorization\Role;
use App\Domain\Pricing\CalculationMode;
use App\Domain\Pricing\Profile;
use App\Domain\Pricing\ProfileAssignment;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Infrastructure\Persistence\Doctrine\DoctrineProfileAssignmentRepository;
use App\Infrastructure\Persistence\Doctrine\DoctrineRoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\SodiumPasswordHasher;
use DateTimeImmutable;

/**
 * US-060 (T-060-02) — écran d'affectation profil↔collaborateur.
 *
 * Sans affectation, la valorisation reste `MISSING_RATE` (cause du finding F2 de la recette US-069).
 * Cet écran fournit le maillon manquant côté web : l'administrateur (MANAGE_PRICING, deny-by-default,
 * règle 11) affecte un collaborateur à un profil de tarification sur une période. POST-Redirect-Get + CSRF.
 */
final class ProfileAssignmentPageTest extends WebTestCase
{
    private const string PASSWORD = 'motdepasse-solide';

    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private TenantId $tenant;
    private string $collaboratorId;
    private string $profileId;

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
            $this->em->getClassMetadata(ProfileAssignment::class),
        ];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        $this->tenant = TenantId::generate();
        new InitializeDefaultRoles(new DoctrineRoleRepository($this->em))->forTenant($this->tenant);

        $hasher = new SodiumPasswordHasher();
        $this->em->persist(new Tenant($this->tenant, 'Agence A'));

        $admin = new User($this->tenant, 'admin@agence.test', $hasher->hash(self::PASSWORD), ['Administrateur']);
        $collaborator = new User($this->tenant, 'camille@agence.test', $hasher->hash(self::PASSWORD), ['Collaborateur']);
        $this->em->persist($admin);
        $this->em->persist($collaborator);
        $this->collaboratorId = $collaborator->id();

        $profile = new Profile($this->tenant, 'Consultant Senior', CalculationMode::LOADED);
        $this->profileId = $profile->id();
        self::getContainer()->get(\App\Domain\Pricing\ProfileRepository::class)->save($profile);

        $this->em->flush();
    }

    protected function tearDown(): void
    {
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->em->close();
        parent::tearDown();
    }

    public function testAdminSeesAssignmentForm(): void
    {
        $this->login('admin@agence.test');

        $crawler = $this->client->request('GET', '/profils', server: ['HTTP_ACCEPT' => 'text/html']);

        self::assertResponseIsSuccessful();
        self::assertGreaterThan(0, $crawler->filter('form[action="/profils/affectations"]')->count());
        self::assertStringContainsString('Affecter un collaborateur', (string) $this->client->getResponse()->getContent());
    }

    public function testAdminAssignsCollaboratorToProfile(): void
    {
        $this->login('admin@agence.test');
        $crawler = $this->client->request('GET', '/profils', server: ['HTTP_ACCEPT' => 'text/html']);

        $form = $crawler->selectButton('Affecter le collaborateur')->form([
            'userId' => $this->collaboratorId,
            'profileId' => $this->profileId,
            'effectiveFrom' => '2026-01-01',
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects('/profils');
        $this->em->clear();
        $assignments = new DoctrineProfileAssignmentRepository($this->em)->findForUser($this->tenant, $this->collaboratorId);
        self::assertCount(1, $assignments);
        self::assertSame($this->profileId, $assignments[0]->profileId());
    }

    public function testOverlappingAssignmentIsRejected(): void
    {
        $this->em->persist(new ProfileAssignment(
            $this->tenant,
            $this->collaboratorId,
            $this->profileId,
            \App\Domain\Shared\EffectivePeriod::since(new DateTimeImmutable('2026-01-01')),
        ));
        $this->em->flush();

        $this->login('admin@agence.test');
        $crawler = $this->client->request('GET', '/profils', server: ['HTTP_ACCEPT' => 'text/html']);
        $form = $crawler->selectButton('Affecter le collaborateur')->form([
            'userId' => $this->collaboratorId,
            'profileId' => $this->profileId,
            'effectiveFrom' => '2026-06-01',
        ]);
        $this->client->submit($form);
        $this->client->followRedirect();

        self::assertStringContainsString('chevauche', (string) $this->client->getResponse()->getContent());
        $this->em->clear();
        $assignments = new DoctrineProfileAssignmentRepository($this->em)->findForUser($this->tenant, $this->collaboratorId);
        self::assertCount(1, $assignments, 'Aucune affectation supplémentaire ne doit être créée.');
    }

    public function testCollaboratorCannotAssign(): void
    {
        $this->login('camille@agence.test');

        $this->client->request('POST', '/profils/affectations', [
            'userId' => $this->collaboratorId,
            'profileId' => $this->profileId,
            'effectiveFrom' => '2026-01-01',
        ]);

        self::assertResponseStatusCodeSame(403);
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
