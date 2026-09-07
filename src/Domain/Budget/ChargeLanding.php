<?php

declare(strict_types=1);

namespace App\Domain\Budget;

/**
 * Atterrissage en charge d'un projet (US-036, EF-PRJ-14/15). `available = false` lorsque le calcul est
 * impossible (pas de budget charge, ou avancement physique nul/absent) — aucune alerte alors. Les
 * montants (`landingCostCents`, `costBudgetCents`) sont sensibles (coût) ; les ratios et le drapeau
 * d'alerte ne révèlent aucun coût unitaire (HAB-1).
 */
final readonly class ChargeLanding
{
    public function __construct(
        public bool $available,
        public ?int $landingCostCents,
        public ?int $costBudgetCents,
        public ?float $overrunPercent,
        public ?float $consumptionPercent,
        public ?int $physicalProgressPercent,
        public bool $isEarlyDrift,
        public bool $isEscalated = false,
    ) {
    }
}
