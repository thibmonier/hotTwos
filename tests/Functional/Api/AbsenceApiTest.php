<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Absence\AbsenceRequest;
use App\Domain\Validation\AbsenceValidationCircuit;
use App\Domain\Absence\AbsenceType;
use App\Domain\Authorization\Role;
use App\Domain\Calendar\ClosurePeriod;
use App\Domain\Calendar\Holiday;
use App\Domain\Calendar\WorkSchedule;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use DateTimeImmutable;
use DateTimeZone;
use App\Infrastructure\Persistence\Doctrine\DoctrineRoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\SodiumPasswordHasher;

/**
 * US-054 (T-054-05/07) — API absences : déclaration (201), liste et compteurs pour soi-même,
 * décision réservée au manager habilité (403 sinon), authentification requise (401).
 */
final class AbsenceApiTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private string $typeId;
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
            $this->em->getClassMetadata(AbsenceType::class),
            $this->em->getClassMetadata(AbsenceRequest::class),
            $this->em->getClassMetadata(AbsenceValidationCircuit::class),
            // US-091b — endpoint d'impact : jours ouvrés (fériés/fermetures/régime) + conflits.
            $this->em->getClassMetadata(Holiday::class),
            $this->em->getClassMetadata(ClosurePeriod::class),
            $this->em->getClassMetadata(WorkSchedule::class),
        ];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        $tenant = TenantId::generate();
        $this->tenant = $tenant;
        new InitializeDefaultRoles(new DoctrineRoleRepository($this->em))->forTenant($tenant);

        $hasher = new SodiumPasswordHasher();
        $this->em->persist(new Tenant($tenant, 'Agence A'));
        $this->em->persist(new User($tenant, 'camille@agence.test', $hasher->hash('motdepasse-solide'), ['Collaborateur']));
        $this->em->persist(new User($tenant, 'marc@agence.test', $hasher->hash('motdepasse-solide'), ['Chef de projet']));
        $type = new AbsenceType($tenant, 'Congés payés');
        $this->typeId = $type->id();
        $this->em->persist($type);
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
        $this->postJson('/api/absences', ['typeId' => $this->typeId, 'startDate' => '2026-09-01', 'endDate' => '2026-09-05']);

        self::assertResponseStatusCodeSame(401);
    }

    public function testDeclareListAndBalance(): void
    {
        $this->login('camille@agence.test');

        $this->postJson('/api/absences', ['typeId' => $this->typeId, 'startDate' => '2026-09-01', 'endDate' => '2026-09-05']);
        self::assertResponseStatusCodeSame(201);

        $this->client->request('GET', '/api/absences', server: ['HTTP_ACCEPT' => 'application/json']);
        self::assertResponseIsSuccessful();
        self::assertCount(1, $this->decodeList());

        $this->client->request('GET', '/api/absences/balance', server: ['HTTP_ACCEPT' => 'application/json']);
        self::assertResponseIsSuccessful();
        $balance = $this->decodeObject();
        self::assertSame(5.0, $balance['pending'] ?? null);
        self::assertSame(20.0, $balance['projectedBalance'] ?? null);
    }

    public function testManagerDecidesButCollaboratorCannot(): void
    {
        $this->login('camille@agence.test');
        $this->postJson('/api/absences', ['typeId' => $this->typeId, 'startDate' => '2026-09-01', 'endDate' => '2026-09-05']);
        $id = $this->decodeObject()['id'] ?? null;
        self::assertIsString($id);

        // Le collaborateur ne peut pas décider.
        $this->postJson('/api/absences/'.$id.'/decision', ['approved' => true]);
        self::assertResponseStatusCodeSame(403);

        // Le manager valide.
        $this->login('marc@agence.test');
        $this->postJson('/api/absences/'.$id.'/decision', ['approved' => true]);
        self::assertResponseIsSuccessful();
        self::assertSame('validated', $this->decodeObject()['status'] ?? null);
    }

    public function testImpactRequiresAuthentication(): void
    {
        // US-091b — /api/absences/impact est couvert par le firewall ^/api/absences (ROLE_USER).
        $this->client->request('GET', '/api/absences/impact?from=2026-09-07&to=2026-09-11', server: ['HTTP_ACCEPT' => 'application/json']);

        self::assertResponseStatusCodeSame(401);
    }

    public function testImpactReturnsBusinessDaysAndProjectedBalance(): void
    {
        // CA-2 : 5 jours ouvrés (lun.→ven., aucun férié/fermeture) ; solde projeté = 25 − 5 = 20.
        $this->login('camille@agence.test');

        $impact = $this->getImpact('2026-09-07', '2026-09-11');

        self::assertSame(5, $impact['businessDays'] ?? null);
        self::assertSame(20.0, $impact['projectedBalance'] ?? null);
        self::assertFalse($impact['hasConflict'] ?? null);
    }

    public function testImpactExcludesClosedDaysAndFlagsClosureConflict(): void
    {
        // CA-2 + CA-3 : une fermeture le mercredi 9 sept. → 4 jours ouvrés au lieu de 5 + conflit signalé.
        $this->em->persist(new ClosurePeriod($this->tenant, $this->day('2026-09-09'), $this->day('2026-09-09'), 'Pont'));
        $this->em->flush();

        $this->login('camille@agence.test');

        $impact = $this->getImpact('2026-09-07', '2026-09-11');

        self::assertSame(4, $impact['businessDays'] ?? null);
        self::assertSame(21.0, $impact['projectedBalance'] ?? null);
        self::assertTrue($impact['hasConflict'] ?? null);
        $message = $impact['conflictMessage'] ?? null;
        self::assertIsString($message);
        self::assertStringContainsString('fermeture', $message);
    }

    public function testImpactFlagsOverlapWithAlreadyPostedAbsence(): void
    {
        // CA-3 : une demande déjà posée qui chevauche la période sélectionnée est signalée.
        $this->login('camille@agence.test');
        $this->postJson('/api/absences', ['typeId' => $this->typeId, 'startDate' => '2026-09-07', 'endDate' => '2026-09-08']);
        self::assertResponseStatusCodeSame(201);

        $impact = $this->getImpact('2026-09-08', '2026-09-09');

        self::assertTrue($impact['hasConflict'] ?? null);
        $message = $impact['conflictMessage'] ?? null;
        self::assertIsString($message);
        self::assertStringContainsString('demande', $message);
    }

    public function testImpactRejectsInvalidRange(): void
    {
        // Erreur : date de fin antérieure à la date de début → 422.
        $this->login('camille@agence.test');

        $this->client->request('GET', '/api/absences/impact?from=2026-09-11&to=2026-09-07', server: ['HTTP_ACCEPT' => 'application/json']);

        self::assertResponseStatusCodeSame(422);
    }

    /**
     * @return array<string, mixed>
     */
    private function getImpact(string $from, string $to): array
    {
        $this->client->request('GET', sprintf('/api/absences/impact?from=%s&to=%s', $from, $to), server: ['HTTP_ACCEPT' => 'application/json']);
        self::assertResponseIsSuccessful();

        return $this->decodeObject();
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function postJson(string $uri, array $payload): void
    {
        $this->client->request('POST', $uri, server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'], content: json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeObject(): array
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);

        return $decoded;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function decodeList(): array
    {
        /** @var list<array<string, mixed>> $decoded */
        $decoded = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);

        return $decoded;
    }

    private function login(string $email): void
    {
        $this->client->request('POST', '/api/login', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['email' => $email, 'password' => 'motdepasse-solide'], JSON_THROW_ON_ERROR));
        self::assertResponseIsSuccessful();
    }

    private function day(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value.' 00:00:00', new DateTimeZone('UTC'));
    }
}
