<?php

declare(strict_types=1);

namespace App\Infrastructure\Budget;

use App\Domain\Budget\MarginDriftThresholdProvider;
use App\Domain\Tenant\TenantId;

/**
 * Seuil de dérive par défaut (US-072, CA-2) : {@see self::DEFAULT_POINTS} points de marge, aligné
 * OBJ-6 (écart ≤ 5 pts). Valeur de référence tant qu'aucun référentiel de seuils tenant (US-018)
 * n'est disponible ; le port {@see MarginDriftThresholdProvider} laisse le seam pour l'override.
 */
final readonly class DefaultMarginDriftThresholdProvider implements MarginDriftThresholdProvider
{
    public const float DEFAULT_POINTS = 5.0;

    public function pointsFor(TenantId $tenant): float
    {
        return self::DEFAULT_POINTS;
    }
}
