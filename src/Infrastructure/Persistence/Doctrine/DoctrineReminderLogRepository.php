<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Reminder\ReminderLog;
use App\Domain\Reminder\ReminderLogRepository;
use App\Domain\Tenant\TenantId;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Implémentation Doctrine du {@see ReminderLogRepository} (US-056, DIP). Tenant explicite.
 */
final readonly class DoctrineReminderLogRepository implements ReminderLogRepository
{
    /** Garde-fou de l'historique : borne dure la lecture même si un appelant demande davantage. */
    private const int MAX_LIMIT = 500;

    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(ReminderLog $log): void
    {
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    public function latestFor(TenantId $tenant, string $userId, DateTimeImmutable $weekStart): ?ReminderLog
    {
        /** @var ReminderLog|null $log */
        $log = $this->entityManager->createQuery(
            'SELECT l FROM '.ReminderLog::class.' l'
            .' WHERE l.tenantId = :tenant AND l.userId = :user AND l.weekStart = :week'
            .' ORDER BY l.sequence DESC',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('user', $userId)
            ->setParameter('week', $weekStart, 'date_immutable')
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $log;
    }

    public function sentOnDay(TenantId $tenant, string $userId, DateTimeImmutable $day): bool
    {
        $dayStart = $day->setTime(0, 0);

        $count = $this->entityManager->createQuery(
            'SELECT COUNT(l.id) FROM '.ReminderLog::class.' l'
            .' WHERE l.tenantId = :tenant AND l.userId = :user'
            .' AND l.sentAt >= :dayStart AND l.sentAt < :nextDay',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('user', $userId)
            ->setParameter('dayStart', $dayStart, 'datetime_immutable')
            ->setParameter('nextDay', $dayStart->modify('+1 day'), 'datetime_immutable')
            ->getSingleScalarResult();

        return is_numeric($count) && (int) $count > 0;
    }

    public function findRecent(TenantId $tenant, ?string $userId, int $limit): array
    {
        $limit = max(1, min($limit, self::MAX_LIMIT));

        $dql = 'SELECT l FROM '.ReminderLog::class.' l WHERE l.tenantId = :tenant';
        if (null !== $userId) {
            $dql .= ' AND l.userId = :user';
        }
        $dql .= ' ORDER BY l.sentAt DESC';

        $query = $this->entityManager->createQuery($dql)
            ->setParameter('tenant', $tenant->toString())
            ->setMaxResults($limit);
        if (null !== $userId) {
            $query->setParameter('user', $userId);
        }

        /** @var list<ReminderLog> $logs */
        $logs = $query->getResult();

        return $logs;
    }
}
