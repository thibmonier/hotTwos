<?php

declare(strict_types=1);

namespace App\Tests\Functional\Web;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Authorization\Role;
use App\Domain\Skill\Skill;
use App\Domain\Skill\SkillAssignment;
use App\Domain\Skill\SkillCategory;
use App\Domain\Skill\SkillLevelScale;
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
 * US-013 (EF-REF-10/11, CA-1/CA-3/CA-5/CA-6) — référentiel de compétences : création, désactivation,
 * refus de doublon (insensible à la casse), association, gating MANAGE_ORGANIZATION.
 */
final class SkillCatalogTest extends WebTestCase
{
    private const string PASSWORD = 'motdepasse-solide';

    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private TenantId $tenant;
    private string $collaboratorId;

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
            $this->em->getClassMetadata(Skill::class),
            $this->em->getClassMetadata(SkillLevelScale::class),
            $this->em->getClassMetadata(SkillAssignment::class),
        ];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        $this->tenant = TenantId::generate();
        new InitializeDefaultRoles(new DoctrineRoleRepository($this->em))->forTenant($this->tenant);

        $hasher = new SodiumPasswordHasher();
        $this->em->persist(new Tenant($this->tenant, 'Agence A'));
        $this->em->persist(new User($this->tenant, 'admin@agence.test', $hasher->hash(self::PASSWORD), ['Administrateur']));
        $collaborator = new User($this->tenant, 'pierre@agence.test', $hasher->hash(self::PASSWORD), ['Chef de projet']);
        $this->em->persist($collaborator);
        $this->collaboratorId = $collaborator->id();
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->em->close();
        parent::tearDown();
    }

    public function testAdminCreatesAndListsSkill(): void
    {
        $this->login('admin@agence.test');
        $token = $this->tokenForForm('action="/parametrage/competences/skill"]');

        $this->client->request('POST', '/parametrage/competences/skill', ['_token' => $token, 'category' => SkillCategory::TECHNIQUE->value, 'label' => 'React.js']);
        self::assertResponseRedirects();

        $this->client->request('GET', '/parametrage/competences');
        self::assertStringContainsString('React.js', (string) $this->client->getResponse()->getContent());
    }

    public function testDuplicateSkillRejectedCaseInsensitive(): void
    {
        $this->em->persist(new Skill($this->tenant, SkillCategory::TECHNIQUE, 'JavaScript'));
        $this->em->flush();

        $this->login('admin@agence.test');
        $this->client->request('POST', '/parametrage/competences/skill', ['_token' => $this->tokenForForm('action="/parametrage/competences/skill"]'), 'category' => SkillCategory::TECHNIQUE->value, 'label' => 'javascript']);
        self::assertResponseRedirects();

        // Le doublon (casse différente) n'a pas créé de 2e entrée.
        $count = (int) $this->em->createQuery('SELECT COUNT(s.id) FROM '.Skill::class.' s')->getSingleScalarResult();
        self::assertSame(1, $count);
    }

    public function testDeactivateAndAssign(): void
    {
        $skill = new Skill($this->tenant, SkillCategory::TECHNIQUE, 'Java');
        $this->em->persist($skill);
        $this->em->flush();

        $this->login('admin@agence.test');

        // Association niveau 3 (échelle par défaut 4 niveaux).
        $this->client->request('POST', '/parametrage/competences/association', ['_token' => $this->tokenForForm('action="/parametrage/competences/association"]'), 'collaborator' => $this->collaboratorId, 'skill' => $skill->id(), 'level' => '3']);
        self::assertResponseRedirects();
        $assignments = (int) $this->em->createQuery('SELECT COUNT(a.id) FROM '.SkillAssignment::class.' a')->getSingleScalarResult();
        self::assertSame(1, $assignments);

        // Désactivation.
        $this->client->request('POST', '/parametrage/competences/skill/'.$skill->id().'/desactivation', ['_token' => $this->tokenForForm('action$="/desactivation"]')]);
        self::assertResponseRedirects();
        $this->em->clear();
        $refreshed = $this->em->find(Skill::class, $skill->id());
        self::assertInstanceOf(Skill::class, $refreshed);
        self::assertFalse($refreshed->isActive());
    }

    public function testNonAdminForbidden(): void
    {
        $this->login('pierre@agence.test');
        $this->client->request('GET', '/parametrage/competences');
        self::assertResponseStatusCodeSame(403);
    }

    private function tokenForForm(string $actionSelector): string
    {
        $crawler = $this->client->request('GET', '/parametrage/competences');
        self::assertResponseIsSuccessful();

        return (string) $crawler->filter('form['.$actionSelector.' input[name="_token"]')->first()->attr('value');
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
