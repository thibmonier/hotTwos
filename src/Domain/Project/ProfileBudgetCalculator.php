<?php

declare(strict_types=1);

namespace App\Domain\Project;

use App\Domain\Pricing\NoEffectiveRateException;
use App\Domain\Pricing\RateResolver;
use App\Domain\Tenant\TenantId;
use DateTimeImmutable;

/**
 * Valorise un budget de charge par profil (US-078, EF-PRJ-9) : pour chaque ligne `{profil, jours}`,
 * multiplie les jours par le taux de vente / coût **en vigueur à la date de référence**
 * ({@see RateResolver::resolveAt}, taux historisés EPIC-001). Un profil sans taux est signalé (CA-4)
 * et n'est pas compté comme 0.
 */
final readonly class ProfileBudgetCalculator
{
    public function __construct(private RateResolver $rates)
    {
    }

    /**
     * @param iterable<LotProfileBudget> $lines
     */
    public function compute(TenantId $tenant, iterable $lines, DateTimeImmutable $referenceDate): ProfileBudgetBreakdown
    {
        $days = 0;
        $sellingCents = 0;
        $costCents = 0;
        $missing = [];

        foreach ($lines as $line) {
            $days += $line->days();
            try {
                $rate = $this->rates->resolveAt($tenant, $line->profileId(), $referenceDate);
                $sellingCents += $rate->sellingPriceCents() * $line->days();
                $costCents += $rate->costPriceCents() * $line->days();
            } catch (NoEffectiveRateException) {
                $missing[] = $line->profileId();
            }
        }

        return new ProfileBudgetBreakdown($days, $sellingCents, $costCents, $missing);
    }
}
