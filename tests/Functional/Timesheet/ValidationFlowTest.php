<?php

declare(strict_types=1);

namespace App\Tests\Functional\Timesheet;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Authorization\Role;
use App\Domain\Project\Project;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use App\Domain\Timesheet\TimeEntry;
use App\Domain\User\User;
use App\Infrastructure\Persistence\Doctrine\DoctrineRoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\SodiumPasswordHasher;
use DateTimeImmutable;

/**
 * US-055 — un chef de projet valide/refuse par lot les imputations de SES projets ;
 * hors de son périmètre c'est 403 ; un refus sans motif est 422.
 */
final class ValidationFlowTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private User $chef;
    private string $ownEntryId;
    private string $foreignEntryId;

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
            $this->em->getClassMetadata(Project::class),
            $this->em->getClassMetadata(TimeEntry::class),
        ];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        $tenant = TenantId::generate();
        new InitializeDefaultRoles(new DoctrineRoleRepository($this->em))->forTenant($tenant);

        $this->chef = new User($tenant, 'marc@agence.test', new SodiumPasswordHasher()->hash('x'), ['Chef de projet']);
        $collaboratorId = TenantId::generate()->toString();
        $otherChefId = TenantId::generate()->toString();
        $own = new Project($tenant, 'PRJ-1', 'Sous ma responsabilité', true, $this->chef->id());
        $foreign = new Project($tenant, 'PRJ-2', 'Autre chef', true, $otherChefId);
        $ownEntry = new TimeEntry($tenant, $collaboratorId, $own->id(), new DateTimeImmutable('2026-09-15'), 240);
        $foreignEntry = new TimeEntry($tenant, $collaboratorId, $foreign->id(), new DateTimeImmutable('2026-09-15'), 180);

        foreach ([new Tenant($tenant, 'Agence A'), $this->chef, $own, $foreign, $ownEntry, $foreignEntry] as $entity) {
            $this->em->persist($entity);
        }
        $this->em->flush();
        $this->ownEntryId = $ownEntry->id();
        $this->foreignEntryId = $foreignEntry->id();
    }

    protected function tearDown(): void
    {
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->em->close();
        parent::tearDown();
    }

    public function testChefValidatesEntriesOnOwnProject(): void
    {
        $this->client->loginUser($this->chef);

        $this->post(['entryIds' => [$this->ownEntryId], 'decision' => 'validate']);

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        self::assertSame(1, $payload['decided'] ?? null);
    }

    public function testValidationOutsideResponsibilityIsForbidden(): void
    {
        $this->client->loginUser($this->chef);

        $this->post(['entryIds' => [$this->foreignEntryId], 'decision' => 'validate']);

        self::assertResponseStatusCodeSame(403);
    }

    public function testRejectionWithoutReasonIsUnprocessable(): void
    {
        $this->client->loginUser($this->chef);

        $this->post(['entryIds' => [$this->ownEntryId], 'decision' => 'reject', 'reason' => '']);

        self::assertResponseStatusCodeSame(422);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function post(array $payload): void
    {
        $this->client->request(
            'POST',
            '/api/time-entries/validate',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload, \JSON_THROW_ON_ERROR),
        );
    }
}
