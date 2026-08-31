<?php

declare(strict_types=1);

namespace App\Tests\Functional\Cli;

use App\Application\Authorization\DefaultRoleMatrix;
use App\Domain\Authorization\Role;
use App\Domain\Tenant\TenantId;
use App\Infrastructure\Persistence\Doctrine\DoctrineRoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * US-003 / CA-4 — la commande CLI applique la matrice via le câblage réel du conteneur
 * (l'alias RoleRepository → implémentation Doctrine est résolu).
 */
final class InitializeDefaultRolesCommandTest extends KernelTestCase
{
    private KernelInterface $bootedKernel;
    private EntityManagerInterface $em;

    /** @var list<\Doctrine\ORM\Mapping\ClassMetadata<object>> */
    private array $schema;

    protected function setUp(): void
    {
        $this->bootedKernel = self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        $this->schema = [$this->em->getClassMetadata(Role::class)];
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

    public function testCommandInitializesTheMatrix(): void
    {
        $tenant = TenantId::generate();

        $tester = new CommandTester(
            new Application($this->bootedKernel)->find('app:tenant:init-roles'),
        );
        $exitCode = $tester->execute(['tenant' => $tenant->toString()]);

        self::assertSame(0, $exitCode);

        $repository = new DoctrineRoleRepository($this->em);
        $this->em->clear();
        foreach (DefaultRoleMatrix::definitions() as $definition) {
            self::assertNotNull($repository->findByName($tenant, $definition->name));
        }
    }

    public function testCommandRejectsInvalidTenant(): void
    {
        $tester = new CommandTester(
            new Application($this->bootedKernel)->find('app:tenant:init-roles'),
        );
        $exitCode = $tester->execute(['tenant' => 'pas-un-uuid']);

        self::assertSame(2, $exitCode, 'Un identifiant invalide doit renvoyer le code INVALID.');
    }
}
