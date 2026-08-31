<?php

declare(strict_types=1);

namespace App\Application\Analytics;

use App\Domain\Analytics\AnalyticsProjector;
use App\Domain\Tenant\TenantId;

/**
 * Cas d'usage de reconstruction complète du modèle analytique d'un tenant (US-005, ARC-114).
 * Fin adaptateur applicatif au-dessus du projecteur (port), déclenché par la CLI ou, plus
 * tard, par la réconciliation périodique (Sprint 2).
 */
final readonly class RebuildAnalytics
{
    public function __construct(private AnalyticsProjector $projector)
    {
    }

    public function forTenant(TenantId $tenant): void
    {
        $this->projector->rebuild($tenant);
    }
}
