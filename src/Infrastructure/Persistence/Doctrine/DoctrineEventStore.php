<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Analytics\DomainEvent;
use App\Domain\Analytics\EventStore;
use App\Domain\Analytics\StoredEvent;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Implémentation Doctrine du flux d'événements (ADR-9, DIP).
 *
 * La séquence par tenant est attribuée à l'ajout (ordre total déterministe pour la
 * reconstruction). Cloisonnement par tenant exprimé explicitement dans les requêtes,
 * correct hors requête HTTP (CLI de reconstruction).
 */
final readonly class DoctrineEventStore implements EventStore
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function append(DomainEvent $event): void
    {
        $stored = new StoredEvent($event, $this->nextSequence($event->tenantId()));
        $this->entityManager->persist($stored);
        $this->entityManager->flush();
    }

    public function streamFor(TenantId $tenant): array
    {
        /** @var list<StoredEvent> $events */
        $events = $this->entityManager->createQuery(
            'SELECT e FROM '.StoredEvent::class.' e WHERE e.tenantId = :tenant ORDER BY e.sequence ASC',
        )
            ->setParameter('tenant', $tenant->toString())
            ->getResult();

        return $events;
    }

    private function nextSequence(TenantId $tenant): int
    {
        $max = $this->entityManager->createQuery(
            'SELECT MAX(e.sequence) FROM '.StoredEvent::class.' e WHERE e.tenantId = :tenant',
        )
            ->setParameter('tenant', $tenant->toString())
            ->getSingleScalarResult();

        return null === $max ? 1 : (int) $max + 1;
    }
}
