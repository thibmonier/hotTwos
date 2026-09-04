<?php

declare(strict_types=1);

namespace App\Application\Analytics\Message;

use App\Application\Messaging\TenantAwareMessage;
use App\Domain\Tenant\TenantId;

/**
 * US-060 (T-060-06) — demande de rematérialisation de `fact_project_revenue` après une valorisation.
 *
 * Émis par {@see \App\Application\Valuation\ValueValidatedTimeHandler} une fois le CA reconnu
 * (événements `RevenueRecognized` appended). Consommé de façon asynchrone (hors requête HTTP) pour
 * rejouer la projection analytique du tenant — la fact table n'était jusqu'ici peuplée que par le
 * batch CLI `app:analytics:rebuild`.
 */
final readonly class AnalyticsRebuildRequested implements TenantAwareMessage
{
    public function __construct(private string $tenantId)
    {
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }
}
