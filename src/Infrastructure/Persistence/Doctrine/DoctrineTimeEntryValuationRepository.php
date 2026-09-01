<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Tenant\TenantId;
use App\Domain\Valuation\TimeEntryValuation;
use App\Domain\Valuation\TimeEntryValuationRepository;
use App\Domain\Valuation\ValuationStatus;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Implémentation Doctrine du {@see TimeEntryValuationRepository} (US-060, DIP). Tenant explicite.
 */
final readonly class DoctrineTimeEntryValuationRepository implements TimeEntryValuationRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(TimeEntryValuation $valuation): void
    {
        $this->entityManager->persist($valuation);
        $this->entityManager->flush();
    }

    public function findForTimeEntry(TenantId $tenant, string $timeEntryId): ?TimeEntryValuation
    {
        /** @var TimeEntryValuation|null $valuation */
        $valuation = $this->entityManager->createQuery(
            'SELECT v FROM '.TimeEntryValuation::class.' v WHERE v.tenantId = :tenant AND v.timeEntryId = :entry',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('entry', $timeEntryId)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $valuation;
    }

    public function findMissingRate(TenantId $tenant): array
    {
        /** @var list<TimeEntryValuation> $valuations */
        $valuations = $this->entityManager->createQuery(
            'SELECT v FROM '.TimeEntryValuation::class.' v WHERE v.tenantId = :tenant AND v.status = :status',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('status', ValuationStatus::MISSING_RATE->value)
            ->getResult();

        return $valuations;
    }
}
