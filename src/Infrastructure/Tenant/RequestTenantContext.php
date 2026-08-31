<?php

declare(strict_types=1);

namespace App\Infrastructure\Tenant;

use App\Application\Tenant\TenantContext;
use App\Application\Tenant\TenantSwitcher;
use App\Domain\Tenant\TenantId;
use LogicException;

/**
 * Porteur du tenant courant pour la durée d'une requête (ARC-61).
 *
 * Service partagé en mode worker : {@see clear()} DOIT être appelé en fin de requête
 * pour ne pas exposer le tenant de la requête précédente (ARC-47, RSQ-15).
 */
final class RequestTenantContext implements TenantContext, TenantSwitcher
{
    private ?TenantId $current = null;

    public function switchTo(TenantId $tenantId): void
    {
        $this->current = $tenantId;
    }

    public function clear(): void
    {
        $this->current = null;
    }

    public function current(): TenantId
    {
        if (!$this->current instanceof TenantId) {
            throw new LogicException('Aucun tenant positionné pour la requête courante.');
        }

        return $this->current;
    }

    public function hasTenant(): bool
    {
        return $this->current instanceof TenantId;
    }
}
