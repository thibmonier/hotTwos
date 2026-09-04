<?php

declare(strict_types=1);

namespace App\Tests\Functional\Web;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Application\User\PasswordResetMailer;
use App\Domain\Authorization\Role;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Infrastructure\Persistence\Doctrine\DoctrineRoleRepository;
use App\Infrastructure\Security\ResetPassword\ResetPasswordRequest;
use App\Tests\Support\User\RecordingPasswordResetMailer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * US-068 (T-068-08) — parcours « mot de passe oublié » : demande → e-mail → réinitialisation → login.
 * Anti-énumération (e-mail inconnu → même écran, aucun envoi) et rejet des jetons invalides.
 */
final class ResetPasswordWebTest extends WebTestCase
{
    private const string OLD_PASSWORD = 'motdepasse-solide';
    private const string NEW_PASSWORD = 'motdepasse-costaud';

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
            $this->em->getClassMetadata(ResetPasswordRequest::class),
        ];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        $this->tenant = TenantId::generate();
        new InitializeDefaultRoles(new DoctrineRoleRepository($this->em))->forTenant($this->tenant);

        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $this->em->persist(new Tenant($this->tenant, 'Agence A'));
        $user = new User($this->tenant, 'camille@agence.test', 'x', ['Collaborateur']);
        $user->changePassword($hasher->hashPassword($user, self::OLD_PASSWORD));
        $this->em->persist($user);
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->em->close();
        parent::tearDown();
    }

    private function mailer(): RecordingPasswordResetMailer
    {
        // En test, le port PasswordResetMailer est aliasé vers l'enregistreur (config when@test).
        /** @var RecordingPasswordResetMailer $mailer */
        $mailer = self::getContainer()->get(PasswordResetMailer::class);

        return $mailer;
    }

    public function testFullResetFlow(): void
    {
        // 1. Demande de réinitialisation.
        $crawler = $this->client->request('GET', '/mot-de-passe-oublie');
        self::assertResponseIsSuccessful();
        $form = $crawler->selectButton('Envoyer le lien')->form(['email' => 'camille@agence.test']);
        $this->client->submit($form);
        self::assertResponseRedirects('/mot-de-passe-oublie/verification');

        // 2. Un lien a été envoyé au compte.
        self::assertCount(1, $this->mailer()->sent);
        $path = (string) parse_url((string) $this->mailer()->lastUrl(), \PHP_URL_PATH);
        self::assertStringStartsWith('/reinitialiser/', $path);

        // 3. Le lien place le jeton en session puis redirige (hors URL).
        $this->client->request('GET', $path);
        self::assertResponseRedirects('/reinitialiser');
        $crawler = $this->client->followRedirect();
        self::assertResponseIsSuccessful();

        // 4. Définition du nouveau mot de passe.
        $form = $crawler->selectButton('Réinitialiser')->form([
            'new_password' => self::NEW_PASSWORD,
            'confirm_password' => self::NEW_PASSWORD,
        ]);
        $this->client->submit($form);
        self::assertResponseRedirects('/login');

        // 5. Le mot de passe a bien changé.
        $this->em->clear();
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => 'camille@agence.test']);
        self::assertInstanceOf(User::class, $user);
        self::assertTrue($hasher->isPasswordValid($user, self::NEW_PASSWORD));
        self::assertFalse($hasher->isPasswordValid($user, self::OLD_PASSWORD));
    }

    public function testTooLongPasswordIsRejected(): void
    {
        // Atteint le formulaire de reset via un lien valide.
        $crawler = $this->client->request('GET', '/mot-de-passe-oublie');
        $this->client->submit($crawler->selectButton('Envoyer le lien')->form(['email' => 'camille@agence.test']));
        $path = (string) parse_url((string) $this->mailer()->lastUrl(), \PHP_URL_PATH);
        $this->client->request('GET', $path);
        $crawler = $this->client->followRedirect();

        $tooLong = str_repeat('a', 200);
        $this->client->submit($crawler->selectButton('Réinitialiser')->form([
            'new_password' => $tooLong,
            'confirm_password' => $tooLong,
        ]));

        // Refusé (borne haute) : pas de redirection vers /login, mot de passe inchangé, pas de 500.
        self::assertResponseIsSuccessful();
        $this->em->clear();
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => 'camille@agence.test']);
        self::assertInstanceOf(User::class, $user);
        self::assertTrue($hasher->isPasswordValid($user, self::OLD_PASSWORD));
    }

    public function testUnknownEmailShowsSameScreenWithoutSending(): void
    {
        $crawler = $this->client->request('GET', '/mot-de-passe-oublie');
        $form = $crawler->selectButton('Envoyer le lien')->form(['email' => 'inconnu@agence.test']);
        $this->client->submit($form);

        // Même écran de confirmation, aucun e-mail envoyé (anti-énumération, CA-3).
        self::assertResponseRedirects('/mot-de-passe-oublie/verification');
        self::assertSame([], $this->mailer()->sent);
    }

    public function testInvalidTokenIsRejected(): void
    {
        $this->client->request('GET', '/reinitialiser/un-jeton-bidon');
        self::assertResponseRedirects('/reinitialiser');
        $this->client->followRedirect();

        // Jeton falsifié → redirection vers la demande avec message générique.
        self::assertResponseRedirects('/mot-de-passe-oublie');
    }
}
