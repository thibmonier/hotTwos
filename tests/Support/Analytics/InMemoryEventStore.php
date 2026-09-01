<?php

declare(strict_types=1);

namespace App\Tests\Support\Analytics;

use App\Domain\Analytics\DomainEvent;
use App\Domain\Analytics\EventStore;
use App\Domain\Analytics\StoredEvent;
use App\Domain\Tenant\TenantId;

/**
 * Double en mémoire du flux d'événements pour les tests unitaires (US-060).
 * Attribue la séquence par tenant, comme l'implémentation Doctrine.
 */
final class InMemoryEventStore implements EventStore
{
    /** @var list<DomainEvent> reconnaissances brutes, dans l'ordre d'ajout (assertions de test) */
    public array $appended = [];

    /** @var list<StoredEvent> */
    private array $stored = [];

    /** @var array<string, int> */
    private array $sequences = [];

    public function append(DomainEvent $event): void
    {
        $tenant = $event->tenantId()->toString();
        $sequence = ($this->sequences[$tenant] ?? 0) + 1;
        $this->sequences[$tenant] = $sequence;

        $this->appended[] = $event;
        $this->stored[] = new StoredEvent($event, $sequence);
    }

    public function streamFor(TenantId $tenant): array
    {
        return array_values(array_filter(
            $this->stored,
            static fn (StoredEvent $event): bool => $event->tenantId()->equals($tenant),
        ));
    }
}
