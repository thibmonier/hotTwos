<?php

declare(strict_types=1);

namespace App\Application\Pricing;

use App\Application\Authorization\Authorizer;
use App\Application\Pricing\Message\ProfileRateDefined;
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
use Symfony\Component\Messenger\MessageBusInterface;
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
    /** Plafond métier des montants (≈ 10 M€) — reste très en deçà de la borne 32 bits de la colonne. */
    private const int MAX_CENTS = 999_999_999;

    public function __construct(
        private Authorizer $authorizer,
        private ProfileRepository $profiles,
        private ProfileRateRepository $rates,
        private ClockInterface $clock,
        private SecurityAuditLogger $audit,
        private MessageBusInterface $bus,
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
        if ($costPriceCents > self::MAX_CENTS || $sellingPriceCents > self::MAX_CENTS) {
            throw new PricingException('Le coût de revient et le taux de vente dépassent le plafond autorisé.');
        }

        $profile = $this->profiles->find($tenant, $profileId);
        if (!$profile instanceof Profile) {
            throw new PricingException(sprintf('Profil introuvable : %s.', $profileId));
        }
        if (!$profile->isActive()) {
            throw new PricingException('Impossible de définir un tarif sur un profil désactivé.');
        }

        $this->guardNoOverlap($tenant, $profileId, $period);

        // Comparaison à la date du jour (minuit) : un tarif effectif « aujourd'hui » n'est pas
        // rétroactif, même en cours de journée (l'heure de now() ne doit pas fausser le test).
        $retroactive = $period->from() < $this->clock->now()->setTime(0, 0);
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

        // Couplage par événement (US-060, CA-4) : un nouveau tarif re-déclenche la valorisation
        // des imputations restées sans tarif pour ce tenant.
        $this->bus->dispatch(new ProfileRateDefined($tenant->toString(), $profileId));

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
