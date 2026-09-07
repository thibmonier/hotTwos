<?php

declare(strict_types=1);

namespace App\Tests\Functional\Web;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Skill\Skill;
use App\Domain\Skill\SkillAssignment;
use App\Domain\Skill\SkillCategory;
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
 * US-040 (EPIC-004) — page de recherche de staffing : résultats par compétence/niveau, gating.
 */
final class StaffingSearchPageTest extends WebTestCase
{
    use ProvisionsFullSchema;

    private const string PASSWORD = 'motdepasse-solide';

    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private TenantId $tenant;
    private string $skillId;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->provisionSchema($this->em);

        $this->tenant = TenantId::generate();
        new InitializeDefaultRoles(new DoctrineRoleRepository($this->em))->forTenant($this->tenant);

        $hasher = new SodiumPasswordHasher();
        $this->em->persist(new Tenant($this->tenant, 'Agence A'));
        $this->em->persist(new User($this->tenant, 'marc@agence.test', $hasher->hash(self::PASSWORD), ['Chef de projet']));
        $alice = new User($this->tenant, 'alice@agence.test', $hasher->hash(self::PASSWORD), ['Collaborateur']);
        $alice->rename('Alice', 'Martin');
        $this->em->persist($alice);

        $skill = new Skill($this->tenant, SkillCategory::TECHNIQUE, 'Java');
        $this->em->persist($skill);
        $this->skillId = $skill->id();
        $this->em->persist(new SkillAssignment($this->tenant, $alice->id(), $this->skillId, 3));
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        $this->dropSchema($this->em);
        $this->em->close();
        parent::tearDown();
    }

    public function testSearchReturnsMatchingCandidate(): void
    {
        $this->login('marc@agence.test');
        $this->client->request('GET', '/planification/recherche?skill='.$this->skillId.'&level=2&period=2026-07');

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Recherche de staffing', $content);
        self::assertStringContainsString('Alice Martin', $content);
    }

    public function testCollaboratorForbidden(): void
    {
        $this->login('alice@agence.test');
        $this->client->request('GET', '/planification/recherche');
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
