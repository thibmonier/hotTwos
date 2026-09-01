<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Organization;

use App\Application\Authorization\Authorizer;
use App\Application\Organization\AttachCollaborator;
use App\Domain\Authorization\AccessDeniedException;
use App\Domain\Authorization\DataScope;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\Role;
use App\Domain\Organization\OrganizationException;
use App\Domain\Organization\OrgUnit;
use App\Domain\Shared\EffectivePeriod;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Tests\Support\Authorization\InMemoryRoleRepository;
use App\Tests\Support\Authorization\RecordingSecurityAuditLogger;
use App\Tests\Support\Organization\InMemoryOrgMembershipRepository;
use App\Tests\Support\Organization\InMemoryOrgUnitRepository;
use App\Tests\Support\User\InMemoryUserRepository;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

/**
 * US-010 (T-010-04, EF-REF-2) — le rattachement d'un collaborateur exige la permission ADMIN,
 * cible une unité active, et interdit tout chevauchement de périodes pour un même collaborateur.
 */
final class AttachCollaboratorTest extends TestCase
{
    private const string USER = '018f9c4e-0000-7000-8000-0000000000aa';

    private TenantId $tenant;
    private InMemoryOrgUnitRepository $units;
    private InMemoryOrgMembershipRepository $memberships;
    private AttachCollaborator $attach;
    private User $admin;
    private User $collaborator;
    private OrgUnit $unit;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $roles = new InMemoryRoleRepository();
        $roles->add(new Role($this->tenant, 'Administrateur', [Permission::MANAGE_ORGANIZATION], DataScope::TENANT));
        $roles->add(new Role($this->tenant, 'Collaborateur', [Permission::VIEW_PROJECT], DataScope::OWN));

        $this->units = new InMemoryOrgUnitRepository();
        $this->memberships = new InMemoryOrgMembershipRepository();
        $users = new InMemoryUserRepository();
        $users->register($this->tenant, self::USER);
        $audit = new RecordingSecurityAuditLogger();
        $this->attach = new AttachCollaborator(new Authorizer($roles, $audit), $this->units, $this->memberships, $users, $audit);

        $this->admin = new User($this->tenant, 'admin@agence.test', 'hash', ['Administrateur']);
        $this->collaborator = new User($this->tenant, 'collab@agence.test', 'hash', ['Collaborateur']);

        $this->unit = new OrgUnit($this->tenant, null, 'Équipe Data');
        $this->units->save($this->unit);
    }

    public function testAdminAttachesACollaborator(): void
    {
        $this->attach->attach($this->tenant, $this->admin, self::USER, $this->unit->id(), EffectivePeriod::since($this->date('2026-01-01')));

        self::assertCount(1, $this->memberships->findForUser($this->tenant, self::USER));
    }

    public function testAttachmentWithoutPermissionIsDenied(): void
    {
        $this->expectException(AccessDeniedException::class);

        $this->attach->attach($this->tenant, $this->collaborator, self::USER, $this->unit->id(), EffectivePeriod::since($this->date('2026-01-01')));
    }

    public function testAttachmentToUnknownUnitFails(): void
    {
        $this->expectException(OrganizationException::class);

        $this->attach->attach($this->tenant, $this->admin, self::USER, 'unknown-unit', EffectivePeriod::since($this->date('2026-01-01')));
    }

    public function testAttachmentToDeactivatedUnitFails(): void
    {
        $this->unit->deactivate();

        $this->expectException(OrganizationException::class);
        $this->attach->attach($this->tenant, $this->admin, self::USER, $this->unit->id(), EffectivePeriod::since($this->date('2026-01-01')));
    }

    public function testAttachmentOfUnknownCollaboratorFails(): void
    {
        $unknownButValidUuid = TenantId::generate()->toString();

        $this->expectException(OrganizationException::class);
        $this->attach->attach($this->tenant, $this->admin, $unknownButValidUuid, $this->unit->id(), EffectivePeriod::since(self::date('2026-01-01')));
    }

    public function testOverlappingAttachmentIsRejected(): void
    {
        $this->attach->attach($this->tenant, $this->admin, self::USER, $this->unit->id(), EffectivePeriod::between($this->date('2026-01-01'), $this->date('2026-07-01')));

        $this->expectException(OrganizationException::class);
        $this->attach->attach($this->tenant, $this->admin, self::USER, $this->unit->id(), EffectivePeriod::between($this->date('2026-03-01'), $this->date('2026-09-01')));
    }

    public function testAdjacentAttachmentIsAllowed(): void
    {
        $this->attach->attach($this->tenant, $this->admin, self::USER, $this->unit->id(), EffectivePeriod::between($this->date('2026-01-01'), $this->date('2026-04-01')));
        $this->attach->attach($this->tenant, $this->admin, self::USER, $this->unit->id(), EffectivePeriod::since($this->date('2026-04-01')));

        self::assertCount(2, $this->memberships->findForUser($this->tenant, self::USER));
    }

    private function date(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value.' 00:00:00', new DateTimeZone('UTC'));
    }
}
