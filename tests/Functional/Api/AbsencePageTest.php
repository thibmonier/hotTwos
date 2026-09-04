<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Domain\Absence\AbsenceRequest;
use App\Domain\Absence\AbsenceType;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\SodiumPasswordHasher;
use DateTimeImmutable;
use DateTimeZone;

/**
 * US-054 (T-054-06) — l'écran « Mes absences » exige une authentification (401) et affiche les
 * compteurs et la liste des demandes du collaborateur.
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
        $this->client->request('POST', '/api/login', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['email' => 'camille@agence.test', 'password' => 'motdepasse-solide'], JSON_THROW_ON_ERROR));
        self::assertResponseIsSuccessful();

        $this->client->request('GET', '/absences');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Mes absences');
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Mes compteurs', $content);
        self::assertStringContainsString('2026-09-01', $content); // la demande apparaît
    }

    private function day(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value.' 00:00:00', new DateTimeZone('UTC'));
    }
}
