<?php

declare(strict_types=1);

namespace App\Application\Messaging;

use App\Domain\Tenant\TenantId;

/**
 * Contrat d'un message asynchrone porteur de son tenant (ARC-47).
 *
 * À la consommation hors requête HTTP, le contexte de tenant ne peut pas être résolu depuis
 * l'utilisateur authentifié : il est porté par le message lui-même. Le
 * {@see \App\Infrastructure\Messaging\TenantContextMiddleware} pose puis efface ce tenant
 * autour du handler, garantissant l'isolation et l'absence d'état résiduel (RSQ-15).
 */
interface TenantAwareMessage
{
    public function tenantId(): TenantId;
}
