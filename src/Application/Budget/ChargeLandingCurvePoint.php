<?php

declare(strict_types=1);

namespace App\Application\Budget;

/**
 * US-079c — un point de la courbe d'atterrissage (une période close).
 *
 * Les montants de coût sont masqués (`null`) sans `VIEW_COLLABORATOR_COST` (gating HAB-1) ; le
 * pourcentage de dépassement et l'avancement restent visibles dès `VIEW_PROJECT_FINANCIALS`.
 */
final readonly class ChargeLandingCurvePoint
{
    public function __construct(
        public string $period,
        public ?int $landingCostCents,
        public ?float $overrunPercent,
        public ?int $physicalProgressPercent,
        public bool $isEarlyDrift,
        public bool $isEscalated,
    ) {
    }
}
