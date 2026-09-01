<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Authorization\Role;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use App\Domain\Timesheet\TimeEntry;
use App\Domain\User\User;
use App\Infrastructure\Persistence\Doctrine\DoctrineRoleRepository;
use App\Infrastructure\Valuation\ConfiguredPeriodClosure;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\SodiumPasswordHasher;
use DateTimeImmutable;
use DateTimeZone;

/**
 * US-060 (T-060-07, CA-5) — l'API de recalcul de valorisation exige l'habilitation
 * RECOMPUTE_VALUATION (401/403), **verrouille** une période clôturée (423 Locked) et recalcule
 * les imputations validées d'une période ouverte.
 */
final class ValuationRecomputeApiTest extends WebTestCase
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
            $this->em->getClassMetadata(TimeEntry::class),
        ];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        $this->tenant = TenantId::generate();
        new InitializeDefaultRoles(new DoctrineRoleRepository($this->em))->forTenant($this->tenant);

        $hasher = new SodiumPasswordHasher();
        $this->em->persist(new Tenant($this->tenant, 'Agence A'));
        $this->em->persist(new User($this->tenant, 'admin@agence.test', $hasher->hash('motdepasse-solide'), ['Administrateur']));
        $this->em->persist(new User($this->tenant, 'marc@agence.test', $hasher->hash('motdepasse-solide'), ['Chef de projet']));
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
        $this->recompute('2026-08');

        self::assertResponseStatusCodeSame(401);
    }

    public function testNonAdminIsForbidden(): void
    {
        $this->login('marc@agence.test');

        $this->recompute('2026-08');

        self::assertResponseStatusCodeSame(403);
    }

    public function testClosedPeriodIsLocked(): void
    {
        // Le client reboote le kernel à chaque requête : on le fige pour que l'override de
        // service survive jusqu'à la requête de recalcul.
        $this->client->disableReboot();
        $this->login('admin@agence.test');
        // US-057 non implémentée : on force la clôture d'août 2026 via le port stubé. Le
        // compilateur DI résout l'alias vers l'id concret : c'est celui-ci qu'on remplace.
        self::getContainer()->set(ConfiguredPeriodClosure::class, new ConfiguredPeriodClosure(['2026-08']));

        $this->recompute('2026-08');

        self::assertResponseStatusCodeSame(423);
        /** @var array{error?: string} $body */
        $body = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertStringContainsString('clôturée', $body['error'] ?? '');
    }

    public function testOpenPeriodRecomputesValidatedEntries(): void
    {
        $this->persistValidatedEntry($this->date('2026-09-10'));
        $this->login('admin@agence.test');

        $this->recompute('2026-09');

        self::assertResponseIsSuccessful();
        /** @var array<string, mixed> $body */
        $body = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('2026-09', $body['period'] ?? null);
        self::assertSame(1, $body['recomputed'] ?? null);
    }

    public function testInvalidPeriodIsRejected(): void
    {
        $this->login('admin@agence.test');

        $this->recompute('2026-13');

        self::assertResponseStatusCodeSame(422);
    }

    private function recompute(string $period): void
    {
        $this->client->request(
            'POST',
            '/api/valorisation/recompute?period='.$period,
            server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            content: '{}',
        );
    }

    private function persistValidatedEntry(DateTimeImmutable $workDate): void
    {
        $entry = new TimeEntry($this->tenant, TenantId::generate()->toString(), TenantId::generate()->toString(), $workDate, 420);
        $entry->validate(TenantId::generate()->toString(), new DateTimeImmutable('2026-09-30 18:00:00', new DateTimeZone('UTC')));
        $this->em->persist($entry);
        $this->em->flush();
    }

    private function login(string $email): void
    {
        $this->client->request(
            'POST',
            '/api/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => $email, 'password' => 'motdepasse-solide'], JSON_THROW_ON_ERROR),
        );
        self::assertResponseIsSuccessful();
    }

    private function date(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value.' 00:00:00', new DateTimeZone('UTC'));
    }
}
