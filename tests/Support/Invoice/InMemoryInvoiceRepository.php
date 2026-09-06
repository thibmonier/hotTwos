<?php

declare(strict_types=1);

namespace App\Tests\Support\Invoice;

use App\Domain\Invoice\Invoice;
use App\Domain\Invoice\InvoiceRepository;
use App\Domain\Tenant\TenantId;

final class InMemoryInvoiceRepository implements InvoiceRepository
{
    /** @var list<Invoice> */
    public array $invoices = [];

    public function save(Invoice $invoice): void
    {
        $this->invoices[] = $invoice;
    }

    public function findForProject(TenantId $tenant, string $projectRef): array
    {
        $found = array_values(array_filter(
            $this->invoices,
            static fn (Invoice $i): bool => $i->tenantId()->equals($tenant) && $i->projectRef() === $projectRef,
        ));
        usort($found, static fn (Invoice $a, Invoice $b): int => $b->period() <=> $a->period());

        return $found;
    }

    public function totalForProjectPeriod(TenantId $tenant, string $projectRef, string $period): int
    {
        $total = 0;
        foreach ($this->invoices as $invoice) {
            if ($invoice->tenantId()->equals($tenant) && $invoice->projectRef() === $projectRef && $invoice->period() === $period) {
                $total += $invoice->amountCents();
            }
        }

        return $total;
    }
}
