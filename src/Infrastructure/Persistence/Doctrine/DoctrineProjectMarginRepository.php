<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Margin\ProjectMargin;
use App\Domain\Margin\ProjectMarginRepository;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Implémentation Doctrine du {@see ProjectMarginRepository} (US-071, DIP). Tenant explicite.
 *
 * Le remplacement d'une période est atomique (purge puis insertions dans une transaction) : la
 * clôture ou une réouverture rejoue le figeage sans laisser de marges orphelines, et sans toucher
 * aux autres périodes (INV-2).
 */
final readonly class DoctrineProjectMarginRepository implements ProjectMarginRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function replaceForPeriod(TenantId $tenant, string $period, array $margins): void
    {
        $this->entityManager->wrapInTransaction(function () use ($tenant, $period, $margins): void {
            $this->entityManager->createQuery(
                'DELETE FROM '.ProjectMargin::class.' m WHERE m.tenantId = :tenant AND m.period = :period',
            )
                ->setParameter('tenant', $tenant->toString())
                ->setParameter('period', $period)
                ->execute();

            foreach ($margins as $margin) {
                $this->entityManager->persist($margin);
            }

            $this->entityManager->flush();
        });
    }

    public function findForPeriod(TenantId $tenant, string $period): array
    {
        /** @var list<ProjectMargin> $margins */
        $margins = $this->entityManager->createQuery(
            'SELECT m FROM '.ProjectMargin::class.' m'
            .' WHERE m.tenantId = :tenant AND m.period = :period ORDER BY m.revenueCents DESC',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('period', $period)
            ->getResult();

        return $margins;
    }
}
