<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Tenant\TenantId;
use App\Domain\Valuation\TimeEntryValuation;
use App\Domain\Valuation\TimeEntryValuationRepository;
use App\Domain\Valuation\ValuationStatus;
use App\Domain\Valuation\ValuationSummary;
use DateTimeImmutable;
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

    public function summaryFor(TenantId $tenant): ValuationSummary
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = $this->entityManager->createQuery(
            'SELECT v.status AS status, COUNT(v.id) AS c,'
            .' COALESCE(SUM(v.revenueCents), 0) AS rev, COALESCE(SUM(v.costCents), 0) AS cost,'
            .' MAX(v.valuedAt) AS latest'
            .' FROM '.TimeEntryValuation::class.' v WHERE v.tenantId = :tenant GROUP BY v.status',
        )->setParameter('tenant', $tenant->toString())->getResult();

        $total = 0;
        $valued = 0;
        $missing = 0;
        $revenue = 0;
        $cost = 0;
        $latest = null;

        foreach ($rows as $row) {
            $count = $this->intOf($row['c']);
            $total += $count;
            $revenue += $this->intOf($row['rev']);
            $cost += $this->intOf($row['cost']);

            $status = $this->statusOf($row['status']);
            if (ValuationStatus::VALUED === $status) {
                $valued += $count;
            } elseif (ValuationStatus::MISSING_RATE === $status) {
                $missing += $count;
            }

            $rowLatest = $this->parseLatest($row['latest']);
            if ($rowLatest instanceof DateTimeImmutable && (!$latest instanceof DateTimeImmutable || $rowLatest > $latest)) {
                $latest = $rowLatest;
            }
        }

        return new ValuationSummary($total, $valued, $missing, $revenue, $cost, $latest);
    }

    public function findValued(TenantId $tenant, int $limit): array
    {
        /** @var list<TimeEntryValuation> $valuations */
        $valuations = $this->entityManager->createQuery(
            'SELECT v FROM '.TimeEntryValuation::class.' v'
            .' WHERE v.tenantId = :tenant AND v.status = :status ORDER BY v.valuedAt DESC',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('status', ValuationStatus::VALUED->value)
            ->setMaxResults($limit)
            ->getResult();

        return $valuations;
    }

    private function intOf(mixed $raw): int
    {
        return is_numeric($raw) ? (int) $raw : 0;
    }

    private function statusOf(mixed $raw): ?ValuationStatus
    {
        if ($raw instanceof ValuationStatus) {
            return $raw;
        }

        return is_string($raw) ? ValuationStatus::from($raw) : null;
    }

    private function parseLatest(mixed $raw): ?DateTimeImmutable
    {
        if ($raw instanceof DateTimeImmutable) {
            return $raw;
        }

        return is_string($raw) && '' !== $raw ? new DateTimeImmutable($raw) : null;
    }
}
