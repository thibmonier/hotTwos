<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Pricing;

use App\Application\Authorization\Authorizer;
use App\Application\Pricing\DefineSellingRate;
use App\Domain\Authorization\AccessDeniedException;
use App\Domain\Authorization\DataScope;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\Role;
use App\Domain\Pricing\CalculationMode;
use App\Domain\Pricing\PricingException;
use App\Domain\Pricing\Profile;
use App\Domain\Pricing\RateScope;
use App\Domain\Shared\EffectivePeriod;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Tests\Support\Authorization\InMemoryRoleRepository;
use App\Tests\Support\Authorization\RecordingSecurityAuditLogger;
use App\Tests\Support\Pricing\InMemoryProfileRepository;
use App\Tests\Support\Pricing\InMemorySellingRateRepository;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

/**
 * US-015 (EF-REF-19) — définition d'une surcharge de taux : habilitation MANAGE_PRICING, anti-chevauchement,
 * rétroactivité confirmée, montant strictement positif.
 */
final class DefineSellingRateTest extends TestCase
{
    private const string CLIENT = '018f9c4e-0000-7000-8000-0000000000d1';

    private TenantId $tenant;
    private InMemoryProfileRepository $profiles;
    private InMemorySellingRateRepository $rates;
    private RecordingSecurityAuditLogger $audit;
    private Authorizer $authorizer;
    private User $admin;
    private User $observer;
    private string $profileId;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $roles = new InMemoryRoleRepository();
        $roles->add(new Role($this->tenant, 'Admin', [Permission::MANAGE_PRICING], DataScope::TENANT));
        $roles->add(new Role($this->tenant, 'Observateur', [Permission::VIEW_PROJECT], DataScope::TENANT));

        $this->profiles = new InMemoryProfileRepository();
        $profile = new Profile($this->tenant, 'Senior', CalculationMode::DIRECT);
        $this->profiles->save($profile);
        $this->profileId = $profile->id();

        $this->rates = new InMemorySellingRateRepository();
        $this->audit = new RecordingSecurityAuditLogger();
        $this->authorizer = new Authorizer($roles, $this->audit);
        $this->admin = new User($this->tenant, 'admin@agence.test', 'hash', ['Admin']);
        $this->observer = new User($this->tenant, 'obs@agence.test', 'hash', ['Observateur']);
    }

    public function testAdminDefinesClientOverride(): void
    {
        $this->useCase()->define($this->tenant, $this->admin, $this->profileId, RateScope::CLIENT, self::CLIENT, EffectivePeriod::since($this->d('2027-07-01')), 850_00);

        self::assertCount(1, $this->rates->findForScope($this->tenant, $this->profileId, RateScope::CLIENT, self::CLIENT));
        self::assertTrue($this->audit->has('selling_rate_defined'));
    }

    public function testObserverIsDenied(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->useCase()->define($this->tenant, $this->observer, $this->profileId, RateScope::CLIENT, self::CLIENT, EffectivePeriod::since($this->d('2027-07-01')), 850_00);
    }

    public function testOverlapIsRejected(): void
    {
        $this->useCase()->define($this->tenant, $this->admin, $this->profileId, RateScope::CLIENT, self::CLIENT, EffectivePeriod::since($this->d('2027-07-01')), 850_00);

        $this->expectException(PricingException::class);
        $this->useCase()->define($this->tenant, $this->admin, $this->profileId, RateScope::CLIENT, self::CLIENT, EffectivePeriod::since($this->d('2027-08-01')), 900_00);
    }

    public function testNonPositiveRateIsRejected(): void
    {
        $this->expectException(PricingException::class);
        $this->useCase()->define($this->tenant, $this->admin, $this->profileId, RateScope::CLIENT, self::CLIENT, EffectivePeriod::since($this->d('2027-07-01')), 0);
    }

    public function testRetroactiveRequiresConfirmation(): void
    {
        // Horloge à 2027-06 ; date d'effet passée sans confirmation → refus.
        $this->expectException(PricingException::class);
        $this->useCase()->define($this->tenant, $this->admin, $this->profileId, RateScope::PROJECT, self::CLIENT, EffectivePeriod::since($this->d('2027-01-01')), 900_00);
    }

    public function testRetroactiveAcceptedWithConfirmation(): void
    {
        $id = $this->useCase()->define($this->tenant, $this->admin, $this->profileId, RateScope::PROJECT, self::CLIENT, EffectivePeriod::since($this->d('2027-01-01')), 900_00, true);
        self::assertNotSame('', $id);
    }

    private function useCase(): DefineSellingRate
    {
        return new DefineSellingRate(
            $this->authorizer,
            $this->profiles,
            $this->rates,
            new MockClock(new DateTimeImmutable('2027-06-01 09:00:00', new DateTimeZone('UTC'))),
            $this->audit,
        );
    }

    private function d(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value.' 00:00:00', new DateTimeZone('UTC'));
    }
}
