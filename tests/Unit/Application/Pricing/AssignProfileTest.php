<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Pricing;

use App\Application\Authorization\Authorizer;
use App\Application\Pricing\AssignProfile;
use App\Domain\Authorization\AccessDeniedException;
use App\Domain\Authorization\DataScope;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\Role;
use App\Domain\Pricing\CalculationMode;
use App\Domain\Pricing\PricingException;
use App\Domain\Pricing\Profile;
use App\Domain\Shared\EffectivePeriod;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Tests\Support\Authorization\InMemoryRoleRepository;
use App\Tests\Support\Authorization\RecordingSecurityAuditLogger;
use App\Tests\Support\Pricing\InMemoryProfileAssignmentRepository;
use App\Tests\Support\Pricing\InMemoryProfileRepository;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

/**
 * US-060 (T-060-01) — l'affectation d'un collaborateur à un profil de tarification exige la permission
 * MANAGE_PRICING, cible un profil actif, et interdit tout chevauchement de périodes pour un même
 * collaborateur. Sans affectation, la valorisation reste MISSING_RATE (cause du finding F2).
 */
final class AssignProfileTest extends TestCase
{
    private const string USER = '018f9c4e-0000-7000-8000-0000000000aa';

    private TenantId $tenant;
    private InMemoryProfileRepository $profiles;
    private InMemoryProfileAssignmentRepository $assignments;
    private AssignProfile $assign;
    private User $admin;
    private User $collaborator;
    private Profile $profile;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $roles = new InMemoryRoleRepository();
        $roles->add(new Role($this->tenant, 'Administrateur', [Permission::MANAGE_PRICING], DataScope::TENANT));
        $roles->add(new Role($this->tenant, 'Collaborateur', [Permission::VIEW_PROJECT], DataScope::OWN));

        $this->profiles = new InMemoryProfileRepository();
        $this->assignments = new InMemoryProfileAssignmentRepository();
        $audit = new RecordingSecurityAuditLogger();
        $this->assign = new AssignProfile(new Authorizer($roles, $audit), $this->profiles, $this->assignments, $audit);

        $this->admin = new User($this->tenant, 'admin@agence.test', 'hash', ['Administrateur']);
        $this->collaborator = new User($this->tenant, 'collab@agence.test', 'hash', ['Collaborateur']);

        $this->profile = new Profile($this->tenant, 'Consultant Senior', CalculationMode::LOADED);
        $this->profiles->save($this->profile);
    }

    public function testAdminAssignsAProfile(): void
    {
        $this->assign->assign($this->tenant, $this->admin, self::USER, $this->profile->id(), EffectivePeriod::since($this->date('2026-01-01')));

        self::assertCount(1, $this->assignments->findForUser($this->tenant, self::USER));
    }

    public function testAssignmentWithoutPermissionIsDenied(): void
    {
        $this->expectException(AccessDeniedException::class);

        $this->assign->assign($this->tenant, $this->collaborator, self::USER, $this->profile->id(), EffectivePeriod::since($this->date('2026-01-01')));
    }

    public function testAssignmentToUnknownProfileFails(): void
    {
        $this->expectException(PricingException::class);

        $this->assign->assign($this->tenant, $this->admin, self::USER, TenantId::generate()->toString(), EffectivePeriod::since($this->date('2026-01-01')));
    }

    public function testAssignmentToDeactivatedProfileFails(): void
    {
        $this->profile->deactivate();

        $this->expectException(PricingException::class);
        $this->assign->assign($this->tenant, $this->admin, self::USER, $this->profile->id(), EffectivePeriod::since($this->date('2026-01-01')));
    }

    public function testInvalidUserIdFails(): void
    {
        $this->expectException(PricingException::class);

        $this->assign->assign($this->tenant, $this->admin, 'not-a-uuid', $this->profile->id(), EffectivePeriod::since($this->date('2026-01-01')));
    }

    public function testOverlappingAssignmentIsRejected(): void
    {
        $this->assign->assign($this->tenant, $this->admin, self::USER, $this->profile->id(), EffectivePeriod::between($this->date('2026-01-01'), $this->date('2026-07-01')));

        $this->expectException(PricingException::class);
        $this->assign->assign($this->tenant, $this->admin, self::USER, $this->profile->id(), EffectivePeriod::between($this->date('2026-03-01'), $this->date('2026-09-01')));
    }

    public function testAdjacentAssignmentIsAllowed(): void
    {
        $this->assign->assign($this->tenant, $this->admin, self::USER, $this->profile->id(), EffectivePeriod::between($this->date('2026-01-01'), $this->date('2026-04-01')));
        $this->assign->assign($this->tenant, $this->admin, self::USER, $this->profile->id(), EffectivePeriod::since($this->date('2026-04-01')));

        self::assertCount(2, $this->assignments->findForUser($this->tenant, self::USER));
    }

    private function date(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value.' 00:00:00', new DateTimeZone('UTC'));
    }
}
