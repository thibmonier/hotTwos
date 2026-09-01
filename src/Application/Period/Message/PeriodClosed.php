<?php

declare(strict_types=1);

namespace App\Application\Period\Message;

use App\Application\Messaging\TenantAwareMessage;
use App\Domain\Tenant\TenantId;

/**
 * Événement de clôture d'une période (US-057, CA-1) : publié sur le bus à la clôture, consommé de
 * façon asynchrone pour déclencher les calculs aval (valorisation, facturation, charges).
 * Porteur de son tenant (ARC-47) pour le rejeu hors requête HTTP.
 */
final readonly class PeriodClosed implements TenantAwareMessage
{
    public function __construct(
        private string $tenantId,
        private string $period,
    ) {
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }

    public function period(): string
    {
        return $this->period;
    }
}
