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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * US-068 (T-068-10) — régression : changer le mot de passe d'un utilisateur invalide ses sessions
 * actives. Le flux « mot de passe oublié » s'appuie sur ce comportement par défaut de Symfony
 * (le token de session est déauthentifié quand le hash du mot de passe change au refresh).
 * `User` n'implémente pas `EquatableInterface` → la comparaison inclut bien le mot de passe.
 */
final class PasswordChangeInvalidatesSessionTest extends WebTestCase
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

        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $this->em->persist(new Tenant($tenant, 'Agence A'));
        $user = new User($tenant, 'camille@agence.test', 'x', ['Collaborateur']);
        $user->changePassword($hasher->hashPassword($user, 'motdepasse-solide'));
        $this->em->persist($user);
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->em->close();
        parent::tearDown();
    }

    public function testChangingPasswordLogsOutExistingSession(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => 'camille@agence.test']);
        self::assertInstanceOf(User::class, $user);
        $this->client->loginUser($user);

        // Session active : accès autorisé à un écran protégé.
        $this->client->request('GET', '/mon-compte');
        self::assertResponseIsSuccessful();

        // Le mot de passe change (ailleurs : reset, ou « Mon compte » depuis un autre appareil).
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $user->changePassword($hasher->hashPassword($user, 'motdepasse-costaud'));
        $this->em->flush();

        // La session existante est désormais invalidée : redirection vers /login.
        $this->client->request('GET', '/mon-compte');
        self::assertResponseRedirects('/login');
    }
}
