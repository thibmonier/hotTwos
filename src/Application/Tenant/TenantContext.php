<?php

declare(strict_types=1);

namespace App\Application\Tenant;

use App\Domain\Tenant\TenantId;
use LogicException;

/**
 * Lecture du tenant courant, consommée par les cas d'usage.
 * La donnée est filtrée à la source selon ce tenant (INV-1, ENF-SEC-4).
 */
interface TenantContext
{
    /**
     * @throws LogicException si aucun tenant n'est positionné pour la requête courante
     */
    public function current(): TenantId;

    public function hasTenant(): bool;
}
