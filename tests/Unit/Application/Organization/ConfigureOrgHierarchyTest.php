<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Organization;

use App\Application\Authorization\Authorizer;
use App\Application\Organization\ConfigureOrgHierarchy;
use App\Domain\Authorization\AccessDeniedException;
use App\Domain\Authorization\DataScope;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\Role;
use App\Domain\Organization\OrganizationException;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Tests\Support\Authorization\InMemoryRoleRepository;
use App\Tests\Support\Authorization\RecordingSecurityAuditLogger;
use App\Tests\Support\Organization\InMemoryOrgUnitRepository;
use PHPUnit\Framework\TestCase;

/**
 * US-010 (T-010-04, CA-5/CA-6) — le paramétrage de la hiérarchie exige la permission ADMIN,
 * refuse les cycles côté serveur, et désactive (jamais ne supprime) une unité (RG-REF-1).
 */
final class ConfigureOrgHierarchyTest extends TestCase
{
    private TenantId $tenant;
    private InMemoryOrgUnitRepository $units;
    private ConfigureOrgHierarchy $configure;
    private User $admin;
    private User $collaborator;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $roles = new InMemoryRoleRepository();
        $roles->add(new Role($this->tenant, 'Administrateur', [Permission::MANAGE_ORGANIZATION], DataScope::TENANT));
        $roles->add(new Role($this->tenant, 'Collaborateur', [Permission::VIEW_PROJECT], DataScope::OWN));

        $this->units = new InMemoryOrgUnitRepository();
        $audit = new RecordingSecurityAuditLogger();
        $this->configure = new ConfigureOrgHierarchy(new Authorizer($roles, $audit), $this->units, $audit);

        $this->admin = new User($this->tenant, 'admin@agence.test', 'hash', ['Administrateur']);
        $this->collaborator = new User($this->tenant, 'collab@agence.test', 'hash', ['Collaborateur']);
    }

    public function testAdminCreatesARootUnit(): void
    {
        $id = $this->configure->createUnit($this->tenant, $this->admin, null, 'Direction générale');

        $unit = $this->units->find($this->tenant, $id);
        self::assertNotNull($unit);
        self::assertTrue($unit->isRoot());
        self::assertSame('Direction générale', $unit->name());
    }

    public function testAdminCreatesAChildUnderAnExistingParent(): void
    {
        $parentId = $this->configure->createUnit($this->tenant, $this->admin, null, 'Direction');
        $childId = $this->configure->createUnit($this->tenant, $this->admin, $parentId, 'Équipe Data');

        self::assertSame($parentId, $this->units->find($this->tenant, $childId)?->parentId());
    }

    public function testCreationWithoutPermissionIsDenied(): void
    {
        $this->expectException(AccessDeniedException::class);

        $this->configure->createUnit($this->tenant, $this->collaborator, null, 'Direction');
    }

    public function testCreationUnderUnknownParentFails(): void
    {
        $this->expectException(OrganizationException::class);

        $this->configure->createUnit($this->tenant, $this->admin, 'unknown-parent', 'Équipe');
    }

    public function testMovingAUnitUnderItselfIsRejected(): void
    {
        $id = $this->configure->createUnit($this->tenant, $this->admin, null, 'Direction');

        $this->expectException(OrganizationException::class);
        $this->configure->moveUnit($this->tenant, $this->admin, $id, $id);
    }

    public function testMovingAUnitUnderOneOfItsDescendantsIsRejected(): void
    {
        $a = $this->configure->createUnit($this->tenant, $this->admin, null, 'A');
        $b = $this->configure->createUnit($this->tenant, $this->admin, $a, 'B');
        $c = $this->configure->createUnit($this->tenant, $this->admin, $b, 'C');

        // Déplacer A sous C (descendant de A) créerait un cycle A→…→C→A.
        $this->expectException(OrganizationException::class);
        $this->configure->moveUnit($this->tenant, $this->admin, $a, $c);
    }

    public function testDeactivateKeepsTheUnitButMarksItInactive(): void
    {
        $id = $this->configure->createUnit($this->tenant, $this->admin, null, 'Direction');

        $this->configure->deactivateUnit($this->tenant, $this->admin, $id);

        // RG-REF-1 : jamais supprimée, seulement désactivée — elle reste dans l'historique.
        $unit = $this->units->find($this->tenant, $id);
        self::assertNotNull($unit);
        self::assertFalse($unit->isActive());
    }
}
