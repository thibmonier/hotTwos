<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Calendar\WorkSchedule;
use App\Domain\Calendar\WorkScheduleRepository;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineWorkScheduleRepository implements WorkScheduleRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(WorkSchedule $schedule): void
    {
        $this->entityManager->persist($schedule);
        $this->entityManager->flush();
    }

    public function delete(WorkSchedule $schedule): void
    {
        $this->entityManager->remove($schedule);
        $this->entityManager->flush();
    }

    public function find(TenantId $tenant, string $userId): ?WorkSchedule
    {
        $schedule = $this->entityManager->createQuery(
            'SELECT s FROM '.WorkSchedule::class.' s WHERE s.tenantId = :tenant AND s.userId = :user',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('user', $userId)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $schedule instanceof WorkSchedule ? $schedule : null;
    }

    public function findForTenant(TenantId $tenant): array
    {
        /** @var list<WorkSchedule> $schedules */
        $schedules = $this->entityManager->createQuery(
            'SELECT s FROM '.WorkSchedule::class.' s WHERE s.tenantId = :tenant',
        )
            ->setParameter('tenant', $tenant->toString())
            ->getResult();

        return $schedules;
    }
}
