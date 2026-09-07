<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Calendar\Holiday;
use App\Domain\Calendar\HolidayRepository;
use App\Domain\Tenant\TenantId;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineHolidayRepository implements HolidayRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(Holiday $holiday): void
    {
        $this->entityManager->persist($holiday);
        $this->entityManager->flush();
    }

    public function delete(Holiday $holiday): void
    {
        $this->entityManager->remove($holiday);
        $this->entityManager->flush();
    }

    public function find(TenantId $tenant, string $id): ?Holiday
    {
        $holiday = $this->entityManager->find(Holiday::class, $id);

        return $holiday instanceof Holiday && $holiday->tenantId()->equals($tenant) ? $holiday : null;
    }

    public function findForTenant(TenantId $tenant): array
    {
        /** @var list<Holiday> $holidays */
        $holidays = $this->entityManager->createQuery(
            'SELECT h FROM '.Holiday::class.' h WHERE h.tenantId = :tenant ORDER BY h.date ASC',
        )
            ->setParameter('tenant', $tenant->toString())
            ->getResult();

        return $holidays;
    }

    public function allDatesForTenant(TenantId $tenant): array
    {
        /** @var list<array{date: DateTimeImmutable}> $rows */
        $rows = $this->entityManager->createQuery(
            'SELECT h.date AS date FROM '.Holiday::class.' h WHERE h.tenantId = :tenant',
        )
            ->setParameter('tenant', $tenant->toString())
            ->getResult();

        return array_map(static fn (array $row): DateTimeImmutable => $row['date'], $rows);
    }

    public function existsForDate(TenantId $tenant, DateTimeImmutable $date): bool
    {
        $count = (int) $this->entityManager->createQuery(
            'SELECT COUNT(h.id) FROM '.Holiday::class.' h WHERE h.tenantId = :tenant AND h.date = :date',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('date', $date->setTime(0, 0))
            ->getSingleScalarResult();

        return $count > 0;
    }
}
