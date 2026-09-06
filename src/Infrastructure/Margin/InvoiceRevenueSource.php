<?php

declare(strict_types=1);

namespace App\Infrastructure\Margin;

use App\Domain\Invoice\InvoiceRepository;
use App\Domain\Margin\RevenueSource;
use App\Domain\Tenant\TenantId;

/**
 * Source de revenu (US-076) : le **facturé réel** (somme des factures émises US-075) s'il est > 0,
 * sinon le **CA reconnu** transmis en repli (ADR-0022). Applique la règle en un seul point (ARC-6).
 */
final readonly class InvoiceRevenueSource implements RevenueSource
{
    public function __construct(private InvoiceRepository $invoices)
    {
    }

    public function revenueFor(TenantId $tenant, string $projectRef, string $period, int $recognizedFallbackCents): int
    {
        $billed = $this->invoices->totalForProjectPeriod($tenant, $projectRef, $period);

        return $billed > 0 ? $billed : $recognizedFallbackCents;
    }
}
