<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Pricing;

use App\Application\Authorization\Authorizer;
use App\Application\Pricing\DefineProfileRate;
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
use App\Application\Pricing\Message\ProfileRateDefined;
use App\Tests\Support\Authorization\InMemoryRoleRepository;
use App\Tests\Support\Authorization\RecordingSecurityAuditLogger;
use App\Tests\Support\Messaging\RecordingMessageBus;
use App\Tests\Support\Pricing\InMemoryProfileRateRepository;
use App\Tests\Support\Pricing\InMemoryProfileRepository;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

/**
 * US-011 (T-011-04) — définition d'un tarif : habilitation, refus des valeurs ≤ 0 (CA-6) et des
 * chevauchements (CA-5), confirmation explicite d'une saisie rétroactive (CA-3, INV-2).
 */
final class DefineProfileRateTest extends TestCase
{
    private TenantId $tenant;
    private InMemoryProfileRepository $profiles;
    private InMemoryProfileRateRepository $rates;
    private RecordingSecurityAuditLogger $audit;
    private RecordingMessageBus $bus;
    private Authorizer $authorizer;
    private DefineProfileRate $define;
    private User $admin;
    private User $collaborator;
    private string $profileId;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $roles = new InMemoryRoleRepository();
        $roles->add(new Role($this->tenant, 'Administrateur', [Permission::MANAGE_PRICING], DataScope::TENANT));
        $roles->add(new Role($this->tenant, 'Collaborateur', [Permission::VIEW_PROJECT], DataScope::OWN));

        $this->profiles = new InMemoryProfileRepository();
        $profile = new Profile($this->tenant, 'Développeur senior', CalculationMode::LOADED);
        $this->profileId = $profile->id();
        $this->profiles->save($profile);

        $this->rates = new InMemoryProfileRateRepository();
        $this->audit = new RecordingSecurityAuditLogger();
        $this->bus = new RecordingMessageBus();
        $this->authorizer = new Authorizer($roles, $this->audit);
        // « Aujourd'hui » figé au 1er juin 2026 : ce qui précède est rétroactif.
        $this->define = new DefineProfileRate(
            $this->authorizer,
            $this->profiles,
            $this->rates,
            new MockClock(new DateTimeImmutable('2026-06-01 00:00:00', new DateTimeZone('UTC'))),
            $this->audit,
            $this->bus,
        );

        $this->admin = new User($this->tenant, 'admin@agence.test', 'hash', ['Administrateur']);
        $this->collaborator = new User($this->tenant, 'collab@agence.test', 'hash', ['Collaborateur']);
    }

    public function testAdminDefinesARate(): void
    {
        $id = $this->define->define($this->tenant, $this->admin, $this->profileId, EffectivePeriod::since($this->date('2026-07-01')), 45000, 78000);

        self::assertCount(1, $this->rates->rates);
        self::assertNotSame('', $id);
    }

    public function testDefiningARatePublishesRevaluationTrigger(): void
    {
        $this->define->define($this->tenant, $this->admin, $this->profileId, EffectivePeriod::since($this->date('2026-07-01')), 45000, 78000);

        self::assertCount(1, $this->bus->dispatched, 'Un tarif défini déclenche la re-valorisation (CA-4).');
        $message = $this->bus->dispatched[0];
        self::assertInstanceOf(ProfileRateDefined::class, $message);
        self::assertSame($this->profileId, $message->profileId());
        self::assertTrue($message->tenantId()->equals($this->tenant));
    }

    public function testDeniedDefinitionPublishesNothing(): void
    {
        try {
            $this->define->define($this->tenant, $this->collaborator, $this->profileId, EffectivePeriod::since($this->date('2026-07-01')), 45000, 78000);
        } catch (AccessDeniedException) {
        }

        self::assertSame([], $this->bus->dispatched, 'Un refus d\'habilitation ne publie aucun événement.');
    }

    public function testWithoutPermissionIsDenied(): void
    {
        $this->expectException(AccessDeniedException::class);

        $this->define->define($this->tenant, $this->collaborator, $this->profileId, EffectivePeriod::since($this->date('2026-07-01')), 45000, 78000);
    }

    public function testUnknownProfileFails(): void
    {
        $this->expectException(PricingException::class);

        $this->define->define($this->tenant, $this->admin, TenantId::generate()->toString(), EffectivePeriod::since($this->date('2026-07-01')), 45000, 78000);
    }

    public function testNonPositiveCostIsRejected(): void
    {
        $this->expectException(PricingException::class);

        $this->define->define($this->tenant, $this->admin, $this->profileId, EffectivePeriod::since($this->date('2026-07-01')), 0, 78000);
    }

    public function testNonPositiveSellingIsRejected(): void
    {
        $this->expectException(PricingException::class);

        $this->define->define($this->tenant, $this->admin, $this->profileId, EffectivePeriod::since($this->date('2026-07-01')), 45000, 0);
    }

    public function testOverlappingRateIsRejected(): void
    {
        $this->define->define($this->tenant, $this->admin, $this->profileId, EffectivePeriod::between($this->date('2026-07-01'), $this->date('2026-10-01')), 45000, 78000);

        $this->expectException(PricingException::class);
        $this->define->define($this->tenant, $this->admin, $this->profileId, EffectivePeriod::between($this->date('2026-08-01'), $this->date('2026-12-01')), 46000, 80000);
    }

    public function testAdjacentRateIsAllowed(): void
    {
        $this->define->define($this->tenant, $this->admin, $this->profileId, EffectivePeriod::between($this->date('2026-07-01'), $this->date('2026-10-01')), 45000, 78000);
        $this->define->define($this->tenant, $this->admin, $this->profileId, EffectivePeriod::since($this->date('2026-10-01')), 46000, 80000);

        self::assertCount(2, $this->rates->rates);
    }

    public function testRetroactiveRateRequiresConfirmation(): void
    {
        // Date d'effet antérieure au « aujourd'hui » figé (2026-06-01) → rétroactif.
        $this->expectException(PricingException::class);
        $this->define->define($this->tenant, $this->admin, $this->profileId, EffectivePeriod::since($this->date('2026-01-01')), 45000, 78000);
    }

    public function testRetroactiveRateIsAcceptedWhenConfirmedAndAudited(): void
    {
        $this->define->define($this->tenant, $this->admin, $this->profileId, EffectivePeriod::since($this->date('2026-01-01')), 45000, 78000, true);

        self::assertCount(1, $this->rates->rates);
        self::assertTrue($this->audit->has('profile_rate_defined_retroactive'));
    }

    public function testRateEffectiveTodayIsNotRetroactiveEvenLaterInTheDay(): void
    {
        // Horloge en cours de journée : un tarif effectif aujourd'hui ne doit pas être rétroactif.
        $define = new DefineProfileRate(
            $this->authorizer,
            $this->profiles,
            $this->rates,
            new MockClock(new DateTimeImmutable('2026-06-01 14:30:00', new DateTimeZone('UTC'))),
            $this->audit,
            $this->bus,
        );

        $define->define($this->tenant, $this->admin, $this->profileId, EffectivePeriod::since($this->date('2026-06-01')), 45000, 78000);

        self::assertCount(1, $this->rates->rates);
    }

    public function testAmountAboveCapIsRejected(): void
    {
        $this->expectException(PricingException::class);

        $this->define->define($this->tenant, $this->admin, $this->profileId, EffectivePeriod::since($this->date('2026-07-01')), 1_000_000_000, 78000);
    }

    public function testRateOnDeactivatedProfileIsRejected(): void
    {
        $this->profiles->find($this->tenant, $this->profileId)?->deactivate();

        $this->expectException(PricingException::class);
        $this->define->define($this->tenant, $this->admin, $this->profileId, EffectivePeriod::since($this->date('2026-07-01')), 45000, 78000);
    }

    private function date(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value.' 00:00:00', new DateTimeZone('UTC'));
    }
}
