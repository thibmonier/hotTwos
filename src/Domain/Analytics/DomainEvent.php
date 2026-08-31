<?php

declare(strict_types=1);

namespace App\Domain\Analytics;

use App\Domain\Tenant\TenantId;
use DateTimeImmutable;

/**
 * Événement de domaine — seule source d'alimentation du modèle analytique (ADR-9, ARC-111).
 *
 * Le modèle en étoile n'est jamais écrit directement : tout fait dérive de la projection
 * d'un événement. Au Walking Skeleton, un unique type de sonde ({@see RevenueRecognized})
 * valide le mécanisme ; les événements métier réels le rejoindront module par module.
 */
interface DomainEvent
{
    public function tenantId(): TenantId;

    /** @return non-empty-string nom stable de l'événement (ex. « revenue_recognized ») */
    public function name(): string;

    public function occurredAt(): DateTimeImmutable;

    /**
     * @return array<string, scalar> charge utile sérialisable (clés stables)
     */
    public function payload(): array;
}
