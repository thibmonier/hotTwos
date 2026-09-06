<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Currency;

use App\Application\Authorization\Authorizer;
use App\Application\Currency\ConfigureCurrency;
use App\Domain\Authorization\AccessDeniedException;
use App\Domain\Authorization\DataScope;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\Role;
use App\Domain\Currency\CurrencyException;
use App\Domain\Shared\EffectivePeriod;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Tests\Support\Authorization\InMemoryRoleRepository;
use App\Tests\Support\Authorization\RecordingSecurityAuditLogger;
use App\Tests\Support\Currency\InMemoryExchangeRateRepository;
use App\Tests\Support\Currency\InMemoryReferenceCurrencyRepository;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

/**
 * US-016 (EF-REF-22) — configuration devises : gating MANAGE_ORGANIZATION, devise de référence,
 * anti-chevauchement des taux.
 */
final class ConfigureCurrencyTest extends TestCase
{
    private TenantId $tenant;
    private InMemoryReferenceCurrencyRepository $references;
    private InMemoryExchangeRateRepository $rates;
    private RecordingSecurityAuditLogger $audit;
    private Authorizer $authorizer;
    private User $admin;
    private User $observer;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $roles = new InMemoryRoleRepository();
        $roles->add(new Role($this->tenant, 'Admin', [Permission::MANAGE_ORGANIZATION], DataScope::TENANT));
        $roles->add(new Role($this->tenant, 'Observateur', [Permission::VIEW_PROJECT], DataScope::TENANT));

        $this->references = new InMemoryReferenceCurrencyRepository();
        $this->rates = new InMemoryExchangeRateRepository();
        $this->audit = new RecordingSecurityAuditLogger();
        $this->authorizer = new Authorizer($roles, $this->audit);
        $this->admin = new User($this->tenant, 'admin@agence.test', 'hash', ['Admin']);
        $this->observer = new User($this->tenant, 'obs@agence.test', 'hash', ['Observateur']);
    }

    public function testSetsAndUpdatesReferenceCurrency(): void
    {
        $this->useCase()->setReferenceCurrency($this->admin, 'chf');

        self::assertSame('CHF', $this->references->findForTenant($this->tenant)?->code());
        self::assertTrue($this->audit->has('reference_currency_set'));

        $this->useCase()->setReferenceCurrency($this->admin, 'usd');
        self::assertSame('USD', $this->references->findForTenant($this->tenant)?->code());
        self::assertCount(1, $this->references->references); // même enregistrement mis à jour
    }

    public function testDefinesExchangeRate(): void
    {
        $this->useCase()->defineExchangeRate($this->admin, 'CHF', EffectivePeriod::since($this->d('2026-01-01')), 1050);

        self::assertCount(1, $this->rates->findForCurrency($this->tenant, 'CHF'));
        self::assertTrue($this->audit->has('exchange_rate_defined'));
    }

    public function testOverlapIsRejected(): void
    {
        $this->useCase()->defineExchangeRate($this->admin, 'CHF', EffectivePeriod::since($this->d('2026-01-01')), 1050);

        $this->expectException(CurrencyException::class);
        $this->useCase()->defineExchangeRate($this->admin, 'CHF', EffectivePeriod::since($this->d('2026-06-01')), 1080);
    }

    public function testObserverIsDenied(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->useCase()->setReferenceCurrency($this->observer, 'CHF');
    }

    private function useCase(): ConfigureCurrency
    {
        return new ConfigureCurrency($this->authorizer, $this->references, $this->rates, $this->audit);
    }

    private function d(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value.' 00:00:00', new DateTimeZone('UTC'));
    }
}
