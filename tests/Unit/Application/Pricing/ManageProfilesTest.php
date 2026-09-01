<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Pricing;

use App\Application\Authorization\Authorizer;
use App\Application\Pricing\ManageProfiles;
use App\Domain\Authorization\AccessDeniedException;
use App\Domain\Authorization\DataScope;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\Role;
use App\Domain\Pricing\CalculationMode;
use App\Domain\Pricing\PricingException;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Tests\Support\Authorization\InMemoryRoleRepository;
use App\Tests\Support\Authorization\RecordingSecurityAuditLogger;
use App\Tests\Support\Pricing\InMemoryProfileRepository;
use PHPUnit\Framework\TestCase;

/**
 * US-011 (T-011-04/05) — gestion des profils : création et désactivation, réservées à l'admin
 * tarification (MANAGE_PRICING), jamais de suppression (RG-REF-1).
 */
final class ManageProfilesTest extends TestCase
{
    private TenantId $tenant;
    private InMemoryProfileRepository $profiles;
    private ManageProfiles $manage;
    private User $admin;
    private User $collaborator;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $roles = new InMemoryRoleRepository();
        $roles->add(new Role($this->tenant, 'Administrateur', [Permission::MANAGE_PRICING], DataScope::TENANT));
        $roles->add(new Role($this->tenant, 'Collaborateur', [Permission::VIEW_PROJECT], DataScope::OWN));

        $this->profiles = new InMemoryProfileRepository();
        $audit = new RecordingSecurityAuditLogger();
        $this->manage = new ManageProfiles(new Authorizer($roles, $audit), $this->profiles, $audit);

        $this->admin = new User($this->tenant, 'admin@agence.test', 'hash', ['Administrateur']);
        $this->collaborator = new User($this->tenant, 'collab@agence.test', 'hash', ['Collaborateur']);
    }

    public function testAdminCreatesAProfile(): void
    {
        $id = $this->manage->create($this->tenant, $this->admin, 'Développeur senior', CalculationMode::LOADED);

        $profile = $this->profiles->find($this->tenant, $id);
        self::assertNotNull($profile);
        self::assertSame('Développeur senior', $profile->name());
        self::assertSame(CalculationMode::LOADED, $profile->calculationMode());
    }

    public function testCreationWithoutPermissionIsDenied(): void
    {
        $this->expectException(AccessDeniedException::class);

        $this->manage->create($this->tenant, $this->collaborator, 'Junior', CalculationMode::DIRECT);
    }

    public function testDeactivateKeepsProfileButInactive(): void
    {
        $id = $this->manage->create($this->tenant, $this->admin, 'Consultant', CalculationMode::DIRECT);

        $this->manage->deactivate($this->tenant, $this->admin, $id);

        self::assertFalse($this->profiles->find($this->tenant, $id)?->isActive());
    }

    public function testDeactivateUnknownProfileFails(): void
    {
        $this->expectException(PricingException::class);

        $this->manage->deactivate($this->tenant, $this->admin, TenantId::generate()->toString());
    }
}
