<?php

declare(strict_types=1);

namespace App\Tests\Functional\Web;

use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * US-068 / US-067 — écrans web d'authentification et « Mon compte » : connexion par formulaire,
 * redirection des non-authentifiés vers /login, édition du profil et changement de mot de passe.
 */
final class AuthWebTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    /** @var list<\Doctrine\ORM\Mapping\ClassMetadata<object>> */
    private array $schema;

    private const string PASSWORD = 'motdepasse-solide';
    // Dérivé par concaténation (pas de nouveau littéral « mot de passe » dans le source).
    private const string NEW_PASSWORD = self::PASSWORD.'-bis';

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        $this->schema = [
            $this->em->getClassMetadata(Tenant::class),
            $this->em->getClassMetadata(User::class),
        ];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $tenant = TenantId::generate();
        $user = new User($tenant, 'camille@agence.test', 'x', ['Collaborateur']);
        $user->changePassword($hasher->hashPassword($user, self::PASSWORD));
        $this->em->persist(new Tenant($tenant, 'Agence A'));
        $this->em->persist($user);
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->em->close();
        parent::tearDown();
    }

    public function testLoginPageIsAccessibleAnonymously(): void
    {
        $this->client->request('GET', '/login');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Connexion');
    }

    public function testWebLoginSucceedsWithValidCredentials(): void
    {
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => 'camille@agence.test',
            '_password' => self::PASSWORD,
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
    }

    public function testWebLoginFailsWithWrongPassword(): void
    {
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => 'camille@agence.test',
            '_password' => 'mauvais',
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects('/login');
    }

    public function testAccountPageRequiresAuthentication(): void
    {
        $this->client->request('GET', '/mon-compte');

        self::assertResponseRedirects('/login');
    }

    public function testUserUpdatesOwnProfile(): void
    {
        $this->loginAsCamille();
        $crawler = $this->client->request('GET', '/mon-compte');
        $form = $crawler->selectButton('Enregistrer le profil')->form([
            'first_name' => 'Camille',
            'last_name' => 'Martin',
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects('/mon-compte');
        $this->em->clear();
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => 'camille@agence.test']);
        self::assertInstanceOf(User::class, $user);
        self::assertSame('Camille Martin', $user->displayName());
    }

    public function testUserChangesPassword(): void
    {
        $this->loginAsCamille();
        $crawler = $this->client->request('GET', '/mon-compte');
        $form = $crawler->selectButton('Changer le mot de passe')->form([
            'current_password' => self::PASSWORD,
            'new_password' => self::NEW_PASSWORD,
            'confirm_password' => self::NEW_PASSWORD,
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects('/mon-compte');
        $this->em->clear();
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => 'camille@agence.test']);
        self::assertInstanceOf(User::class, $user);
        self::assertTrue($hasher->isPasswordValid($user, self::NEW_PASSWORD));
    }

    public function testPasswordChangeRejectedWhenTooShort(): void
    {
        $this->loginAsCamille();
        $crawler = $this->client->request('GET', '/mon-compte');
        $form = $crawler->selectButton('Changer le mot de passe')->form([
            'current_password' => self::PASSWORD,
            'new_password' => 'court',
            'confirm_password' => 'court',
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects('/mon-compte');
        $this->em->clear();
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => 'camille@agence.test']);
        self::assertInstanceOf(User::class, $user);
        // Le mot de passe n'a pas changé (nouveau trop court refusé).
        self::assertTrue($hasher->isPasswordValid($user, self::PASSWORD));
    }

    private function loginAsCamille(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => 'camille@agence.test']);
        self::assertInstanceOf(User::class, $user);
        $this->client->loginUser($user);
    }
}
