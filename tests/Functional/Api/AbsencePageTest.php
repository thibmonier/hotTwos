<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Domain\Absence\AbsenceRequest;
use App\Domain\Absence\AbsenceType;
use App\Domain\Calendar\ClosurePeriod;
use App\Domain\Calendar\Holiday;
use App\Domain\Calendar\WorkSchedule;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Domain\Validation\AbsenceValidationCircuit;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\SodiumPasswordHasher;

/**
 * US-054 (T-054-06) — l'écran « Mes absences » exige une authentification (401) et affiche les
 * compteurs et la liste des demandes du collaborateur.
 *
 * US-091b — le calendrier du mois distingue fériés, fermetures d'entreprise, absences déjà posées et
 * week-ends, chaque marquage portant une alternative textuelle (aria-label + légende), jamais la
 * couleur seule (WCAG 1.4.1).
 */
final class AbsencePageTest extends WebTestCase
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
            $this->em->getClassMetadata(AbsenceType::class),
            $this->em->getClassMetadata(AbsenceRequest::class),
            $this->em->getClassMetadata(AbsenceValidationCircuit::class),
            $this->em->getClassMetadata(Holiday::class),
            $this->em->getClassMetadata(ClosurePeriod::class),
            // US-107 : le solde interroge WorkingDaysCalculator (régime du collaborateur).
            $this->em->getClassMetadata(WorkSchedule::class),
        ];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        $tenant = TenantId::generate();
        $hasher = new SodiumPasswordHasher();
        $camille = new User($tenant, 'camille@agence.test', $hasher->hash('motdepasse-solide'));
        $this->em->persist(new Tenant($tenant, 'Agence A'));
        $this->em->persist($camille);
        $type = new AbsenceType($tenant, 'Congés payés');
        $this->em->persist($type);
        $this->em->persist(new AbsenceRequest($tenant, $camille->id(), $type->id(), $this->day('2026-09-01'), $this->day('2026-09-05'), true, true, $this->day('2026-08-01')));
        // US-091b — conflits du mois de septembre 2026 : un jour férié et une fermeture d'entreprise.
        $this->em->persist(new Holiday($tenant, $this->day('2026-09-16'), 'Saint Michel'));
        $this->em->persist(new ClosurePeriod($tenant, $this->day('2026-09-21'), $this->day('2026-09-21'), 'Séminaire'));
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->em->close();
        parent::tearDown();
    }

    public function testUnauthenticatedIsRejected(): void
    {
        $this->client->request('GET', '/absences');

        // US-068 : route web → redirection vers la page de connexion (plus de 401 web).
        self::assertResponseRedirects('/login');
    }

    public function testCollaboratorSeesCountersAndRequests(): void
    {
        $this->login();

        $this->client->request('GET', '/absences?month=2026-09');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Mes absences');
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Mes compteurs', $content);
        self::assertStringContainsString('2026-09-01', $content); // la demande apparaît
    }

    public function testCalendarDistinguishesConflictsWithTextAlternatives(): void
    {
        $this->login();

        $this->client->request('GET', '/absences?month=2026-09');

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();

        // En-tête du mois affiché.
        self::assertStringContainsString('Septembre 2026', $content);

        // CA-1 : chaque marquage a une alternative TEXTUELLE (aria-label), jamais la couleur seule.
        self::assertStringContainsString('mon absence déjà posée', $content);   // demande posée (1→5 sept.)
        self::assertStringContainsString('férié : Saint Michel', $content);      // jour férié (16 sept.)
        self::assertStringContainsString('fermeture : Séminaire', $content);     // fermeture (21 sept.)
        self::assertStringContainsString('— week-end', $content);                // week-ends

        // Légende texte (redondance non colorée).
        self::assertStringContainsString('Ma demande', $content);
        self::assertStringContainsString('Fermeture / férié', $content);
        self::assertStringContainsString('Week-end', $content);

        // Structure d'accessibilité : liste de jours + alternatives portées par aria-label.
        self::assertStringContainsString('role="list"', $content);
        self::assertStringContainsString('aria-label="16 — férié : Saint Michel"', $content);
    }

    public function testCalendarRendersEmptyMonthWithoutError(): void
    {
        // CA-4 : un mois sans férié / fermeture / absence s'affiche normalement, sans erreur.
        $this->login();

        $this->client->request('GET', '/absences?month=2026-11');

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Novembre 2026', $content);
        self::assertStringNotContainsString('mon absence déjà posée', $content);
        self::assertStringNotContainsString('férié : Saint Michel', $content);
    }

    private function login(): void
    {
        $this->client->request('POST', '/api/login', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['email' => 'camille@agence.test', 'password' => 'motdepasse-solide'], JSON_THROW_ON_ERROR));
        self::assertResponseIsSuccessful();
    }

    private function day(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value.' 00:00:00', new DateTimeZone('UTC'));
    }
}
