<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Pricing\ProfileAssignment;
use App\Domain\Pricing\ProfileAssignmentRepository;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Implémentation Doctrine du {@see ProfileAssignmentRepository} (US-060, DIP). Tenant explicite.
 */
final readonly class DoctrineProfileAssignmentRepository implements ProfileAssignmentRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(ProfileAssignment $assignment): void
    {
        $this->entityManager->persist($assignment);
        $this->entityManager->flush();
    }

    public function findForUser(TenantId $tenant, string $userId): array
    {
        /** @var list<ProfileAssignment> $assignments */
        $assignments = $this->entityManager->createQuery(
            'SELECT a FROM '.ProfileAssignment::class.' a'
            .' WHERE a.tenantId = :tenant AND a.userId = :user'
            .' ORDER BY a.effectiveFrom DESC',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('user', $userId)
            ->getResult();

        return $assignments;
    }
}
