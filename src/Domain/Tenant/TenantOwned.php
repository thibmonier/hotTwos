<?php

declare(strict_types=1);

namespace App\Domain\Tenant;

/**
 * Marque une entité comme portée par un tenant (INV-1).
 * Toute entité TenantOwned est filtrée automatiquement à la source par le tenant
 * courant (première barrière d'isolation — ARC-33 ; la seconde est la RLS PostgreSQL, ARC-34).
 */
interface TenantOwned
{
    public function tenantId(): TenantId;
}
