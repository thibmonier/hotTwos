<?php

declare(strict_types=1);

namespace App\Domain\Valuation;

use App\Domain\Tenant\TenantId;

/**
 * Port de statut de clôture d'une période comptable (US-060, CA-5 — préfigure US-057).
 *
 * La valorisation interroge ce contrat avant tout recalcul manuel : une période clôturée
 * verrouille le recalcul (423) sans réouverture formelle. L'implémentation définitive
 * viendra avec US-057 ; un stub piloté par configuration tient le rôle en attendant.
 */
interface PeriodClosureStatus
{
    /**
     * @param non-empty-string $period mois au format YYYY-MM
     */
    public function isClosed(TenantId $tenant, string $period): bool;
}
