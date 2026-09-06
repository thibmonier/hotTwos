<?php

declare(strict_types=1);

namespace App\Application\Currency;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\SecurityAuditLogger;
use App\Domain\Currency\CurrencyException;
use App\Domain\Currency\ExchangeRate;
use App\Domain\Currency\ExchangeRateRepository;
use App\Domain\Currency\ReferenceCurrency;
use App\Domain\Currency\ReferenceCurrencyRepository;
use App\Domain\Shared\EffectivePeriod;
use App\Domain\User\User;

/**
 * Configuration des devises d'un tenant (US-016, EF-REF-22) : devise de référence et taux de change
 * datés. Habilitation `MANAGE_ORGANIZATION` ; anti-chevauchement des taux (INV-2) ; tracé (HAB-6).
 */
final readonly class ConfigureCurrency
{
    public function __construct(
        private Authorizer $authorizer,
        private ReferenceCurrencyRepository $references,
        private ExchangeRateRepository $rates,
        private SecurityAuditLogger $audit,
    ) {
    }

    public function setReferenceCurrency(User $user, string $code): void
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_ORGANIZATION);
        $tenant = $user->tenantId();

        $reference = $this->references->findForTenant($tenant);
        if ($reference instanceof ReferenceCurrency) {
            $reference->change($code);
        } else {
            $reference = new ReferenceCurrency($tenant, $code);
        }
        $this->references->save($reference);

        $this->audit->record('reference_currency_set', $tenant->toString(), $user->getUserIdentifier(), ['code' => $reference->code()]);
    }

    public function defineExchangeRate(User $user, string $code, EffectivePeriod $period, int $rateToReferenceMillis): string
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_ORGANIZATION);
        $tenant = $user->tenantId();

        if ($rateToReferenceMillis <= 0) {
            throw new CurrencyException('Le taux de change doit être strictement positif.');
        }

        $normalized = strtoupper(trim($code));
        foreach ($this->rates->findForCurrency($tenant, $normalized) as $existing) {
            if ($existing->period()->overlaps($period)) {
                throw new CurrencyException('Chevauchement de périodes pour le taux de change '.$normalized.'.');
            }
        }

        $rate = new ExchangeRate($tenant, $normalized, $period, $rateToReferenceMillis);
        $this->rates->save($rate);

        $this->audit->record('exchange_rate_defined', $tenant->toString(), $user->getUserIdentifier(), [
            'code' => $normalized,
            'rate_millis' => (string) $rateToReferenceMillis,
        ]);

        return $rate->id();
    }
}
