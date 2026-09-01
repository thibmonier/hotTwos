<?php

declare(strict_types=1);

namespace App\Domain\Tenant;

/**
 * Registre des tenants enregistrés (INV-1) — lecture système hors périmètre d'un tenant unique.
 *
 * Réservé aux traitements d'infrastructure qui itèrent l'ensemble des tenants (ex. le cron de
 * relances US-056), jamais exposé à un utilisateur : la table `tenant` n'est pas cloisonnée.
 */
interface TenantRegistry
{
    /**
     * @return list<TenantId>
     */
    public function allIds(): array;
}
