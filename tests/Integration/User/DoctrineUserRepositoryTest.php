<?php

declare(strict_types=1);

namespace App\Tests\Integration\User;

use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Infrastructure\Persistence\Doctrine\DoctrineUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * F-S5-4 — `findEmailsByIds` résout les e-mails des collaborateurs pour les afficher (complétude)
 * au lieu d'un identifiant technique, strictement cloisonné au tenant demandé.
 */
final class DoctrineUserRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private DoctrineUserRepository $repository;

    /** @var list<\Doctrine\ORM\Mapping\ClassMetadata<object>> */
    private array $schema;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->repository = new DoctrineUserRepository($this->em);

        $this->schema = [$this->em->getClassMetadata(User::class)];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);
    }

    protected function tearDown(): void
    {
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->em->close();
        parent::tearDown();
    }

    public function testResolvesEmailsForKnownIdsWithinTenant(): void
    {
        $tenant = TenantId::generate();
        $camille = new User($tenant, 'camille@agence.test', 'hash', ['Collaborateur']);
        $marc = new User($tenant, 'marc@agence.test', 'hash', ['Chef de projet']);
        $this->em->persist($camille);
        $this->em->persist($marc);
        $this->em->flush();
        $this->em->clear();

        $emails = $this->repository->findEmailsByIds($tenant, [$camille->id(), $marc->id(), TenantId::generate()->toString()]);

        self::assertSame('camille@agence.test', $emails[$camille->id()]);
        self::assertSame('marc@agence.test', $emails[$marc->id()]);
        // L'identifiant inconnu est simplement absent (pas d'erreur).
        self::assertCount(2, $emails);
    }

    public function testDoesNotLeakEmailsAcrossTenants(): void
    {
        $tenantA = TenantId::generate();
        $tenantB = TenantId::generate();
        $foreign = new User($tenantB, 'intrus@autre.test', 'hash', ['Collaborateur']);
        $this->em->persist($foreign);
        $this->em->flush();
        $this->em->clear();

        $emails = $this->repository->findEmailsByIds($tenantA, [$foreign->id()]);

        self::assertSame([], $emails);
    }

    public function testReturnsEmptyForEmptyInput(): void
    {
        self::assertSame([], $this->repository->findEmailsByIds(TenantId::generate(), []));
    }
}
