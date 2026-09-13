<?php

declare(strict_types=1);

namespace App\Tests\Functional\Web;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Reminder\ReminderLog;
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
 * US-092b (CPL-04/CPL-05) — relance inline (sélection + POST sans quitter l'écran) et contrôles de
 * filtre/recherche de la grille de complétude. La relance réutilise le canal existant (journal +
 * notifier US-056) ; réservée à `MANAGE_REMINDERS` (403 sinon) ; CSRF requis.
 */
final class CompletenessReminderTest extends WebTestCase
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
        // Chef de projet : VIEW_TEAM_COMPLETENESS + MANAGE_REMINDERS. Collaborateur : ni l'un ni l'autre.
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

    public function testManagerSeesSelectionAndFilterControls(): void
    {
        $this->login('marc@agence.test');

        $this->client->request('GET', '/completude');

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('data-controller="completeness"', $content);      // CPL-04/05 Stimulus
        self::assertStringContainsString('data-completeness-target="checkbox"', $content); // sélection (CPL-04)
        self::assertStringContainsString('Relancer la sélection', $content);              // action inline (CPL-04)
        self::assertStringContainsString('data-completeness-target="status"', $content);   // filtre statut (CPL-05)
        self::assertStringContainsString('data-completeness-target="search"', $content);   // recherche (CPL-05)
    }

    public function testCollaboratorDoesNotSeeSelectionControls(): void
    {
        $this->login('camille@agence.test');

        $this->client->request('GET', '/completude');

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringNotContainsString('Relancer la sélection', $content);
        self::assertStringNotContainsString('data-completeness-target="checkbox"', $content);
    }

    public function testManagerRemindsSelectedCollaborator(): void
    {
        $this->login('marc@agence.test');
        $token = $this->remindToken();
        $camilleId = $this->userId('camille@agence.test');

        $this->client->request('POST', '/completude/relances', ['_token' => $token, 'userIds' => [$camilleId]]);

        self::assertResponseIsSuccessful();
        /** @var array<string, mixed> $body */
        $body = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(1, $body['sent'] ?? null);

        $logged = (int) $this->em->createQuery(
            'SELECT COUNT(l.id) FROM '.ReminderLog::class.' l WHERE l.userId = :uid'
        )->setParameter('uid', $camilleId)->getSingleScalarResult();
        self::assertSame(1, $logged);
    }

    public function testRemindWithoutSelectionIsRejected(): void
    {
        $this->login('marc@agence.test');
        $token = $this->remindToken();

        $this->client->request('POST', '/completude/relances', ['_token' => $token]);

        self::assertResponseStatusCodeSame(422);
        /** @var array<string, mixed> $body */
        $body = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('error', $body);
    }

    public function testRemindForbiddenForNonManager(): void
    {
        $this->login('camille@agence.test');
        $marcId = $this->userId('marc@agence.test');

        // Le collaborateur ne peut pas relancer (autorisation vérifiée avant le CSRF).
        $this->client->request('POST', '/completude/relances', ['_token' => 'peu-importe', 'userIds' => [$marcId]]);

        self::assertResponseStatusCodeSame(403);
    }

    private function remindToken(): string
    {
        $crawler = $this->client->request('GET', '/completude');
        self::assertResponseIsSuccessful();

        return (string) $crawler->filter('[data-completeness-token-value]')->attr('data-completeness-token-value');
    }

    private function userId(string $email): string
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        self::assertInstanceOf(User::class, $user);

        return $user->id();
    }

    private function login(string $email): void
    {
        $this->client->request('POST', '/api/login', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['email' => $email, 'password' => self::PASSWORD], JSON_THROW_ON_ERROR));
        self::assertResponseIsSuccessful();
    }
}
