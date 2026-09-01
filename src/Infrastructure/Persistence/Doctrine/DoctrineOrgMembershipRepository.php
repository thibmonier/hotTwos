<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Organization\OrgMembership;
use App\Domain\Organization\OrgMembershipRepository;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Implémentation Doctrine du {@see OrgMembershipRepository} (US-010, DIP).
 * Cloisonnement par tenant explicite (en plus du filtre ORM et de la RLS).
 */
final readonly class DoctrineOrgMembershipRepository implements OrgMembershipRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(OrgMembership $membership): void
    {
        $this->entityManager->persist($membership);
        $this->entityManager->flush();
    }

    public function findForUser(TenantId $tenant, string $userId): array
    {
        /** @var list<OrgMembership> $memberships */
        $memberships = $this->entityManager->createQuery(
            'SELECT m FROM '.OrgMembership::class.' m'
            .' WHERE m.tenantId = :tenant AND m.userId = :user'
            .' ORDER BY m.effectiveFrom DESC',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('user', $userId)
            ->getResult();

        return $memberships;
    }

    public function findForOrgUnit(TenantId $tenant, string $orgUnitId): array
    {
        /** @var list<OrgMembership> $memberships */
        $memberships = $this->entityManager->createQuery(
            'SELECT m FROM '.OrgMembership::class.' m'
            .' WHERE m.tenantId = :tenant AND m.orgUnitId = :unit'
            .' ORDER BY m.effectiveFrom DESC',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('unit', $orgUnitId)
            ->getResult();

        return $memberships;
    }
}
