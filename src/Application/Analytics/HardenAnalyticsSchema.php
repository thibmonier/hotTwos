<?php

declare(strict_types=1);

namespace App\Application\Analytics;

use App\Domain\Analytics\AnalyticsSchemaGuard;

/**
 * Cas d'usage d'application du durcissement analytique (US-005, CA-4/CA-6). Fin
 * adaptateur au-dessus du port, déclenché par la commande d'ops.
 */
final readonly class HardenAnalyticsSchema
{
    public function __construct(private AnalyticsSchemaGuard $guard)
    {
    }

    public function apply(): void
    {
        $this->guard->harden();
    }
}
