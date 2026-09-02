<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Project\ExceptionalImputationOpening;
use App\Domain\Project\ExceptionalImputationOpeningRepository;
use App\Domain\Tenant\TenantId;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Implémentation Doctrine du {@see ExceptionalImputationOpeningRepository} (US-037, DIP). Tenant explicite.
 */
final readonly class DoctrineExceptionalImputationOpeningRepository implements ExceptionalImputationOpeningRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(ExceptionalImputationOpening $opening): void
    {
        $this->entityManager->persist($opening);
        $this->entityManager->flush();
    }

    public function coversDay(TenantId $tenant, string $projectId, string $userId, DateTimeImmutable $day): bool
    {
        $weekStart = $day->modify('monday this week')->setTime(0, 0);
        $count = $this->entityManager->createQuery(
            'SELECT COUNT(o.id) FROM '.ExceptionalImputationOpening::class.' o'
            .' WHERE o.tenantId = :tenant AND o.projectId = :project AND o.userId = :user AND o.weekStart = :week',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('project', $projectId)
            ->setParameter('user', $userId)
            ->setParameter('week', $weekStart, 'date_immutable')
            ->getSingleScalarResult();

        return is_numeric($count) && (int) $count > 0;
    }

    public function findForProject(TenantId $tenant, string $projectId): array
    {
        /** @var list<ExceptionalImputationOpening> $openings */
        $openings = $this->entityManager->createQuery(
            'SELECT o FROM '.ExceptionalImputationOpening::class.' o WHERE o.tenantId = :tenant AND o.projectId = :project ORDER BY o.grantedAt DESC',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('project', $projectId)
            ->getResult();

        return $openings;
    }
}
