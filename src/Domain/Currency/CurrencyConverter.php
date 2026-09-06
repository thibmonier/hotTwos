<?php

declare(strict_types=1);

namespace App\Domain\Currency;

use App\Domain\Tenant\TenantId;
use DateTimeImmutable;

/**
 * Convertit un montant vers la **devise de référence** du tenant (US-016, EF-REF-22) au taux en vigueur
 * à une date (INV-2). Devise = référence → identité. Aucun taux à la date → résultat indisponible (CA-4,
 * pas de conversion silencieuse). Défaut EUR si aucune devise de référence configurée.
 */
final readonly class CurrencyConverter
{
    public function __construct(
        private ReferenceCurrencyRepository $references,
        private ExchangeRateRepository $rates,
    ) {
    }

    public function toReference(TenantId $tenant, int $cents, string $currency, DateTimeImmutable $date): ConversionResult
    {
        $currency = strtoupper(trim($currency));
        $reference = $this->references->findForTenant($tenant)?->code() ?? ReferenceCurrency::DEFAULT_CODE;

        if ($currency === $reference) {
            return new ConversionResult($cents, true);
        }

        foreach ($this->rates->findForCurrency($tenant, $currency) as $rate) {
            if ($rate->period()->contains($date)) {
                return new ConversionResult((int) round($cents * $rate->rateToReferenceMillis() / 1000), true);
            }
        }

        return new ConversionResult(null, false);
    }
}
