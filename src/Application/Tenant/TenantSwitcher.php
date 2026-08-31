<?php

declare(strict_types=1);

namespace App\Application\Tenant;

use App\Domain\Tenant\TenantId;

/**
 * Positionnement du tenant en début de requête et effacement en fin (ARC-61).
 * Réservé aux adaptateurs entrants (middleware HTTP, commande CLI) — jamais aux cas d'usage.
 * L'effacement est impératif en mode worker pour éviter toute fuite d'état (ARC-47, RSQ-15).
 */
interface TenantSwitcher
{
    public function switchTo(TenantId $tenantId): void;

    public function clear(): void;
}
