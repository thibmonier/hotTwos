<?php

declare(strict_types=1);

namespace App\Application\Pricing;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\SecurityAuditLogger;
use App\Domain\Pricing\PricingException;
use App\Domain\Pricing\Profile;
use App\Domain\Pricing\ProfileRepository;
use App\Domain\Pricing\RateScope;
use App\Domain\Pricing\SellingRate;
use App\Domain\Pricing\SellingRateRepository;
use App\Domain\Shared\EffectivePeriod;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use Psr\Clock\ClockInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Définit une **surcharge de taux de vente** (client ou projet) pour un profil (US-015, EF-REF-19).
 * Habilitation `MANAGE_PRICING` ; refus des valeurs ≤ 0 et des chevauchements de périodes pour un même
 * (profil, scope, ref) ; une saisie rétroactive exige une confirmation explicite (INV-2, tracée). Une
 * révision n'altère jamais les entrées passées : on en ajoute une.
 */
final readonly class DefineSellingRate
{
    private const int MAX_CENTS = 999_999_999;

    public function __construct(
        private Authorizer $authorizer,
        private ProfileRepository $profiles,
        private SellingRateRepository $rates,
        private ClockInterface $clock,
        private SecurityAuditLogger $audit,
    ) {
    }

    public function define(
        TenantId $tenant,
        User $actor,
        string $profileId,
        RateScope $scope,
        string $scopeRefId,
        EffectivePeriod $period,
        int $sellingPriceCents,
        bool $confirmRetroactive = false,
    ): string {
        $this->authorizer->ensureCan($actor, Permission::MANAGE_PRICING);

        if (!Uuid::isValid($profileId) || !Uuid::isValid($scopeRefId)) {
            throw new PricingException('Identifiant invalide.');
        }
        if ($sellingPriceCents <= 0 || $sellingPriceCents > self::MAX_CENTS) {
            throw new PricingException('Le taux de vente doit être strictement positif.');
        }
        if (!$this->profiles->find($tenant, $profileId) instanceof Profile) {
            throw new PricingException('Profil introuvable.');
        }

        foreach ($this->rates->findForScope($tenant, $profileId, $scope, $scopeRefId) as $existing) {
            if ($existing->period()->overlaps($period)) {
                throw new PricingException('Chevauchement de périodes pour ce taux de vente (scope '.$scope->label().').');
            }
        }

        if ($period->from() < $this->clock->now()->setTime(0, 0) && !$confirmRetroactive) {
            throw new PricingException('Saisie rétroactive : confirmation requise (INV-2).');
        }

        $rate = new SellingRate($tenant, $profileId, $scope, $scopeRefId, $period, $sellingPriceCents);
        $this->rates->save($rate);

        $this->audit->record('selling_rate_defined', $tenant->toString(), $actor->getUserIdentifier(), [
            'profile' => $profileId,
            'scope' => $scope->value,
            'ref' => $scopeRefId,
            'selling_cents' => (string) $sellingPriceCents,
        ]);

        return $rate->id();
    }
}
