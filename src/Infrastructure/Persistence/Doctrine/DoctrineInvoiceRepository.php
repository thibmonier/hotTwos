<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Invoice\Invoice;
use App\Domain\Invoice\InvoiceRepository;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Implémentation Doctrine du {@see InvoiceRepository} (US-075, DIP). Tenant explicite.
 */
final readonly class DoctrineInvoiceRepository implements InvoiceRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(Invoice $invoice): void
    {
        $this->entityManager->persist($invoice);
        $this->entityManager->flush();
    }

    public function findForProject(TenantId $tenant, string $projectRef): array
    {
        /** @var list<Invoice> $invoices */
        $invoices = $this->entityManager->createQuery(
            'SELECT i FROM '.Invoice::class.' i WHERE i.tenantId = :tenant AND i.projectRef = :project ORDER BY i.period DESC, i.issuedAt DESC',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('project', $projectRef)
            ->getResult();

        return $invoices;
    }

    public function totalForProjectPeriod(TenantId $tenant, string $projectRef, string $period): int
    {
        $raw = $this->entityManager->createQuery(
            'SELECT COALESCE(SUM(i.amountCents), 0) FROM '.Invoice::class.' i'
            .' WHERE i.tenantId = :tenant AND i.projectRef = :project AND i.period = :period',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('project', $projectRef)
            ->setParameter('period', $period)
            ->getSingleScalarResult();

        return is_numeric($raw) ? (int) $raw : 0;
    }
}
