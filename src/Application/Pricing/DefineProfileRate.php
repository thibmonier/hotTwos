<?php

declare(strict_types=1);

namespace App\Application\Pricing;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\SecurityAuditLogger;
use App\Domain\Pricing\PricingException;
use App\Domain\Pricing\Profile;
use App\Domain\Pricing\ProfileRate;
use App\Domain\Pricing\ProfileRateRepository;
use App\Domain\Pricing\ProfileRepository;
use App\Domain\Shared\EffectivePeriod;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use Psr\Clock\ClockInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Définition d'une entrée tarifaire pour un profil (US-011, T-011-04).
 *
 * Règles serveur (ARC-19) : habilitation MANAGE_PRICING ; refus des valeurs ≤ 0 (CA-6) et des
 * chevauchements de périodes (CA-5, via EffectivePeriod::overlaps) ; une saisie **rétroactive**
 * (date d'effet antérieure à aujourd'hui) exige une confirmation explicite et est tracée
 * (CA-3, RG-REF-4, INV-2). Une révision n'altère jamais les entrées passées : on en ajoute une.
 */
final readonly class DefineProfileRate
{
    public function __construct(
        private Authorizer $authorizer,
        private ProfileRepository $profiles,
        private ProfileRateRepository $rates,
        private ClockInterface $clock,
        private SecurityAuditLogger $audit,
    ) {
    }

    public function define(
        TenantId $tenant,
        User $actor,
        string $profileId,
        EffectivePeriod $period,
        int $costPriceCents,
        int $sellingPriceCents,
        bool $confirmRetroactive = false,
    ): string {
        $this->authorizer->ensureCan($actor, Permission::MANAGE_PRICING);

        if (!Uuid::isValid($profileId)) {
            throw new PricingException('Identifiant de profil invalide.');
        }
        if ($costPriceCents <= 0 || $sellingPriceCents <= 0) {
            throw new PricingException('Le coût de revient et le taux de vente doivent être strictement positifs.');
        }

        $profile = $this->profiles->find($tenant, $profileId);
        if (!$profile instanceof Profile) {
            throw new PricingException(sprintf('Profil introuvable : %s.', $profileId));
        }

        $this->guardNoOverlap($tenant, $profileId, $period);

        $retroactive = $period->from() < $this->clock->now();
        if ($retroactive && !$confirmRetroactive) {
            throw new PricingException('Saisie tarifaire rétroactive : une confirmation explicite est requise (les valorisations passées ne seront pas réécrites).');
        }

        $rate = new ProfileRate($tenant, $profileId, $period, $costPriceCents, $sellingPriceCents);
        $this->rates->save($rate);

        $this->audit->record(
            $retroactive ? 'profile_rate_defined_retroactive' : 'profile_rate_defined',
            $tenant->toString(),
            $actor->getUserIdentifier(),
            ['profile' => $profileId, 'rate' => $rate->id(), 'effective_from' => $period->from()->format('Y-m-d')],
        );

        return $rate->id();
    }

    private function guardNoOverlap(TenantId $tenant, string $profileId, EffectivePeriod $period): void
    {
        foreach ($this->rates->findForProfile($tenant, $profileId) as $existing) {
            if ($existing->period()->overlaps($period)) {
                throw new PricingException('La période chevauche une entrée tarifaire existante pour ce profil.');
            }
        }
    }
}
