<?php

declare(strict_types=1);

namespace App\Domain\Invoice;

use App\Domain\Tenant\TenantId;

/**
 * Port de persistance des factures (US-075, DIP). Tenant explicite.
 */
interface InvoiceRepository
{
    public function save(Invoice $invoice): void;

    /**
     * Factures d'un projet, triées de la période la plus récente à la plus ancienne.
     *
     * @return list<Invoice>
     */
    public function findForProject(TenantId $tenant, string $projectRef): array;

    /**
     * Total facturé (centimes) pour un (projet, période) — somme des factures émises. 0 si aucune.
     * Source du « facturé réel » consommé par US-076/077.
     */
    public function totalForProjectPeriod(TenantId $tenant, string $projectRef, string $period): int;
}
