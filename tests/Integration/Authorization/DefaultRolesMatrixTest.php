<?php

declare(strict_types=1);

namespace App\Tests\Integration\Authorization;

use App\Application\Authorization\DefaultRoleMatrix;
use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\Role;
use App\Domain\Tenant\TenantId;
use App\Infrastructure\Persistence\Doctrine\DoctrineRoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * US-003 / CA-4 — la matrice de rôles par défaut est reproductible par paramétrage,
 * conforme à la référence et idempotente (relancer ne crée pas de doublon).
 */
final class DefaultRolesMatrixTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private DoctrineRoleRepository $repository;
    private InitializeDefaultRoles $initialize;

    /** @var list<\Doctrine\ORM\Mapping\ClassMetadata<object>> */
    private array $schema;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->repository = new DoctrineRoleRepository($this->em);
        $this->initialize = new InitializeDefaultRoles($this->repository);

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

    public function testCreatesEveryReferenceRoleExactlyAsDefined(): void
    {
        $tenant = TenantId::generate();

        $this->initialize->forTenant($tenant);
        $this->em->clear();

        foreach (DefaultRoleMatrix::definitions() as $definition) {
            $role = $this->repository->findByName($tenant, $definition->name);
            self::assertNotNull($role, sprintf('Rôle « %s » manquant.', $definition->name));
            self::assertTrue(
                $definition->matches($role),
                sprintf('Le rôle « %s » ne correspond pas à la matrice de référence.', $definition->name),
            );
        }
    }

    public function testChefDeProjetNeverGetsCollaboratorCost(): void
    {
        $tenant = TenantId::generate();

        $this->initialize->forTenant($tenant);
        $this->em->clear();

        $chef = $this->repository->findByName($tenant, 'Chef de projet');
        self::assertNotNull($chef);
        self::assertFalse($chef->grants(Permission::VIEW_COLLABORATOR_COST), 'HAB-1 violé : le chef de projet voit le coût.');
    }

    public function testReapplyingIsIdempotent(): void
    {
        $tenant = TenantId::generate();

        $this->initialize->forTenant($tenant);
        $this->initialize->forTenant($tenant);
        $this->em->clear();

        $expected = count(DefaultRoleMatrix::definitions());
        $count = (int) $this->em->createQuery(
            'SELECT COUNT(r.id) FROM '.Role::class.' r WHERE r.tenantId = :tenant',
        )->setParameter('tenant', $tenant->toString())->getSingleScalarResult();

        self::assertSame($expected, $count, 'La ré-application a créé des doublons.');
    }
}
