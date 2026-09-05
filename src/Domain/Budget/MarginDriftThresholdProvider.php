<?php

declare(strict_types=1);

namespace App\Domain\Budget;

use App\Domain\Tenant\TenantId;

/**
 * Fournit le seuil de dérive du taux de marge (en points de %) applicable à un tenant (US-072, CA-2).
 *
 * Port (DIP) : l'implémentation par défaut renvoie une valeur de référence (OBJ-6 : 5 points),
 * paramétrable par tenant. Le raccordement à un référentiel de seuils persistant (US-018) se fera par
 * une implémentation dédiée, sans changer les appelants.
 */
interface MarginDriftThresholdProvider
{
    /** Valeur de référence du seuil de dérive (points) — OBJ-6, à défaut de configuration tenant. */
    public const float DEFAULT_POINTS = 5.0;

    public function pointsFor(TenantId $tenant): float;
}
