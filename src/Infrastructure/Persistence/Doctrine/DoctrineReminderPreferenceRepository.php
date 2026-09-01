<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Reminder\ReminderPreference;
use App\Domain\Reminder\ReminderPreferenceRepository;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Implémentation Doctrine du {@see ReminderPreferenceRepository} (US-056, DIP). Tenant explicite.
 */
final readonly class DoctrineReminderPreferenceRepository implements ReminderPreferenceRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(ReminderPreference $preference): void
    {
        $this->entityManager->persist($preference);
        $this->entityManager->flush();
    }

    public function findForUser(TenantId $tenant, string $userId): ?ReminderPreference
    {
        /** @var ReminderPreference|null $preference */
        $preference = $this->entityManager->createQuery(
            'SELECT p FROM '.ReminderPreference::class.' p WHERE p.tenantId = :tenant AND p.userId = :user',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('user', $userId)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $preference;
    }

    public function findOptedOutUserIds(TenantId $tenant): array
    {
        /** @var list<array{userId: string}> $rows */
        $rows = $this->entityManager->createQuery(
            'SELECT p.userId AS userId FROM '.ReminderPreference::class.' p'
            .' WHERE p.tenantId = :tenant AND p.optedOut = true',
        )
            ->setParameter('tenant', $tenant->toString())
            ->getResult();

        return array_map(static fn (array $row): string => $row['userId'], $rows);
    }
}
