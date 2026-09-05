<?php

declare(strict_types=1);

namespace App\Infrastructure\Budget;

use App\Domain\Budget\MarginDriftThreshold;
use App\Domain\Budget\MarginDriftThresholdProvider;
use App\Domain\Budget\MarginDriftThresholdRepository;
use App\Domain\Tenant\TenantId;

/**
 * Seuil de dérive résolu par tenant (US-018) : lit le seuil configuré ; à défaut, retombe sur la
 * valeur de référence {@see DefaultMarginDriftThresholdProvider::DEFAULT_POINTS} (OBJ-6). Remplace
 * l'implémentation par défaut sans changer le moteur de dérive (US-072) — DIP/OCP.
 */
final readonly class TenantMarginDriftThresholdProvider implements MarginDriftThresholdProvider
{
    public function __construct(private MarginDriftThresholdRepository $thresholds)
    {
    }

    public function pointsFor(TenantId $tenant): float
    {
        $configured = $this->thresholds->findForTenant($tenant);

        return $configured instanceof MarginDriftThreshold
            ? (float) $configured->points()
            : DefaultMarginDriftThresholdProvider::DEFAULT_POINTS;
    }
}
