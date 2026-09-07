<?php

declare(strict_types=1);

namespace App\Tests\Functional\Web;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Calendar\WorkSchedule;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Infrastructure\Persistence\Doctrine\DoctrineRoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\SodiumPasswordHasher;
use App\Tests\Support\Schema\ProvisionsFullSchema;

/**
 * US-021 (EF-REF-7, CA-3/CA-5/CA-6) — paramétrage des régimes de travail : CRUD admin, refus régime
 * vide, gating MANAGE_ORGANIZATION.
 */
final class WorkScheduleConfigTest extends WebTestCase
{
    use ProvisionsFullSchema;

    private const string PASSWORD = 'motdepasse-solide';

    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private TenantId $tenant;
    private string $collaboratorId;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        $this->provisionSchema($this->em);

        $this->tenant = TenantId::generate();
        new InitializeDefaultRoles(new DoctrineRoleRepository($this->em))->forTenant($this->tenant);

        $hasher = new SodiumPasswordHasher();
        $this->em->persist(new Tenant($this->tenant, 'Agence A'));
        $this->em->persist(new User($this->tenant, 'admin@agence.test', $hasher->hash(self::PASSWORD), ['Administrateur']));
        $collaborator = new User($this->tenant, 'marie@agence.test', $hasher->hash(self::PASSWORD), ['Collaborateur']);
        $this->em->persist($collaborator);
        $this->collaboratorId = $collaborator->id();
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        $this->dropSchema($this->em);
        $this->em->close();
        parent::tearDown();
    }

    public function testAdminSetsAndRemovesSchedule(): void
    {
        $this->login('admin@agence.test');
        $token = $this->token('work_schedule_save');

        // Temps partiel : Lun-Jeu.
        $this->client->request('POST', '/parametrage/regimes-travail', ['_token' => $token, 'collaborator' => $this->collaboratorId, 'weekdays' => ['1', '2', '3', '4']]);
        self::assertResponseRedirects();

        $schedule = $this->em->createQuery('SELECT s FROM '.WorkSchedule::class.' s')->getOneOrNullResult();
        self::assertInstanceOf(WorkSchedule::class, $schedule);
        self::assertSame([1, 2, 3, 4], $schedule->workingWeekdays());

        // Suppression → retour temps plein (jeton du formulaire de suppression rendu pour ce collaborateur).
        $deleteToken = (string) $this->client->request('GET', '/parametrage/regimes-travail')
            ->filter('form[action$="/suppression"] input[name="_token"]')->first()->attr('value');
        $this->client->request('POST', '/parametrage/regimes-travail/'.$this->collaboratorId.'/suppression', ['_token' => $deleteToken]);
        self::assertResponseRedirects();
        $count = (int) $this->em->createQuery('SELECT COUNT(s.id) FROM '.WorkSchedule::class.' s')->getSingleScalarResult();
        self::assertSame(0, $count);
    }

    public function testEmptyScheduleRejected(): void
    {
        $this->login('admin@agence.test');
        $this->client->request('POST', '/parametrage/regimes-travail', ['_token' => $this->token('work_schedule_save'), 'collaborator' => $this->collaboratorId, 'weekdays' => []]);
        self::assertResponseRedirects();

        $count = (int) $this->em->createQuery('SELECT COUNT(s.id) FROM '.WorkSchedule::class.' s')->getSingleScalarResult();
        self::assertSame(0, $count);
    }

    public function testNonAdminForbidden(): void
    {
        $this->login('marie@agence.test');
        $this->client->request('GET', '/parametrage/regimes-travail');
        self::assertResponseStatusCodeSame(403);
    }

    private function token(string $intention): string
    {
        $crawler = $this->client->request('GET', '/parametrage/regimes-travail');
        self::assertResponseIsSuccessful();
        unset($intention); // le jeton du formulaire principal (work_schedule_save)

        return (string) $crawler->filter('form[action="/parametrage/regimes-travail"] input[name="_token"]')->first()->attr('value');
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
