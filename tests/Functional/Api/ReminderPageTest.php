<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Absence\AbsenceRequest;
use App\Domain\Authorization\Role;
use App\Domain\Reminder\ReminderLog;
use App\Domain\Reminder\ReminderPreference;
use App\Domain\Reminder\ReminderRule;
use App\Domain\Reminder\ReminderRuleRepository;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use App\Domain\Timesheet\TimeEntry;
use App\Domain\User\User;
use App\Infrastructure\Persistence\Doctrine\DoctrineReminderRuleRepository;
use App\Infrastructure\Persistence\Doctrine\DoctrineRoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\SodiumPasswordHasher;

/**
 * US-056 (T-056-05) — écran `/relances` : réservé à l'habilité (403 sinon), configuration via
 * POST-Redirect-Get (jeton CSRF), bornes invalides signalées sans persistance.
 */
final class ReminderPageTest extends WebTestCase
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
            $this->em->getClassMetadata(ReminderRule::class),
            $this->em->getClassMetadata(ReminderPreference::class),
            $this->em->getClassMetadata(ReminderLog::class),
            $this->em->getClassMetadata(TimeEntry::class),
            $this->em->getClassMetadata(AbsenceRequest::class),
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

    public function testCollaboratorIsForbidden(): void
    {
        $this->login('camille@agence.test');
        $this->client->request('GET', '/relances');

        self::assertResponseStatusCodeSame(403);
    }

    public function testManagerSeesTheConfigurationScreen(): void
    {
        $this->login('marc@agence.test');
        $this->client->request('GET', '/relances');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Règle de relance', $this->client->getResponse()->getContent() ?: '');
    }

    public function testManagerUpdatesTheRule(): void
    {
        $this->login('marc@agence.test');
        $crawler = $this->client->request('GET', '/relances');

        $token = $crawler->filter('input[name="_token"]')->attr('value') ?? '';
        $this->client->request('POST', '/relances', [
            '_token' => $token,
            'initialDelayDays' => '2',
            'frequencyDays' => '5',
            'channel' => 'both',
            'escalationEnabled' => 'on',
            'active' => 'on',
        ]);

        self::assertResponseRedirects('/relances');

        $rule = $this->ruleRepository()->findForTenant($this->tenant);
        self::assertNotNull($rule);
        self::assertSame(5, $rule->frequencyDays());
    }

    public function testInvalidFrequencyIsRejectedWithoutPersisting(): void
    {
        $this->login('marc@agence.test');
        $crawler = $this->client->request('GET', '/relances');
        $token = $crawler->filter('input[name="_token"]')->attr('value') ?? '';

        $this->client->request('POST', '/relances', [
            '_token' => $token,
            'initialDelayDays' => '1',
            'frequencyDays' => '0',
            'channel' => 'in_app',
            'active' => 'on',
        ]);

        self::assertResponseRedirects('/relances');
        self::assertNull($this->ruleRepository()->findForTenant($this->tenant), 'Une fréquence invalide ne doit rien persister.');
    }

    private function ruleRepository(): ReminderRuleRepository
    {
        $this->em->clear();

        return new DoctrineReminderRuleRepository($this->em);
    }

    private function login(string $email): void
    {
        $this->client->request('POST', '/api/login', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['email' => $email, 'password' => 'motdepasse-solide'], JSON_THROW_ON_ERROR));
        self::assertResponseIsSuccessful();
    }
}
