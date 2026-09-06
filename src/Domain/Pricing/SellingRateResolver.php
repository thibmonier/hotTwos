<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

use App\Domain\Tenant\TenantId;
use DateTimeImmutable;

/**
 * Résout le taux de vente applicable à un profil selon la **règle de priorité unique** (US-015,
 * EF-REF-19, ARC-6) : surcharge **projet** > surcharge **client** > taux **profil** (repli sur
 * {@see ProfileRate} via {@see RateResolver}). Les taux sont historisés à date d'effet (INV-2) : on
 * retient l'entrée en vigueur à la date fournie. Expose le niveau retenu.
 */
final readonly class SellingRateResolver
{
    public function __construct(
        private SellingRateRepository $overrides,
        private RateResolver $profileRates,
    ) {
    }

    public function resolve(TenantId $tenant, string $profileId, ?string $clientId, ?string $projectId, DateTimeImmutable $date): ResolvedSellingRate
    {
        if (null !== $projectId) {
            $rate = $this->overrideAt($tenant, $profileId, RateScope::PROJECT, $projectId, $date);
            if (null !== $rate) {
                return new ResolvedSellingRate($rate, RateScope::PROJECT->label());
            }
        }

        if (null !== $clientId) {
            $rate = $this->overrideAt($tenant, $profileId, RateScope::CLIENT, $clientId, $date);
            if (null !== $rate) {
                return new ResolvedSellingRate($rate, RateScope::CLIENT->label());
            }
        }

        // Repli : taux profil (US-011). Propage NoEffectiveRateException si aucun taux profil.
        $profileRate = $this->profileRates->resolveAt($tenant, $profileId, $date);

        return new ResolvedSellingRate($profileRate->sellingPriceCents(), 'profil');
    }

    private function overrideAt(TenantId $tenant, string $profileId, RateScope $scope, string $scopeRefId, DateTimeImmutable $date): ?int
    {
        foreach ($this->overrides->findForScope($tenant, $profileId, $scope, $scopeRefId) as $rate) {
            if ($rate->period()->contains($date)) {
                return $rate->sellingPriceCents();
            }
        }

        return null;
    }
}
