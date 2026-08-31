<?php

declare(strict_types=1);

namespace App\Domain\Analytics;

use App\Domain\Tenant\TenantId;

/**
 * Port du flux d'événements (ADR-9). Append-only : on ajoute des événements, on rejoue
 * le flux d'un tenant pour reconstruire le modèle analytique. L'implémentation Doctrine
 * vit en infrastructure (DIP).
 */
interface EventStore
{
    public function append(DomainEvent $event): void;

    /**
     * Flux ordonné (par `sequence`) d'un tenant.
     *
     * @return list<StoredEvent>
     */
    public function streamFor(TenantId $tenant): array;
}
