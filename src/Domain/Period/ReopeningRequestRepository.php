<?php

declare(strict_types=1);

namespace App\Domain\Period;

use App\Domain\Tenant\TenantId;
use DateTimeImmutable;

/**
 * Port de persistance des demandes de réouverture (US-057, DIP). Tenant explicite.
 */
interface ReopeningRequestRepository
{
    public function save(ReopeningRequest $request): void;

    public function findById(TenantId $tenant, string $id): ?ReopeningRequest;

    /**
     * Réouverture **active** (approuvée, non expirée) pour une période — lève le verrou (CA-2).
     */
    public function findActiveForPeriod(TenantId $tenant, string $period, DateTimeImmutable $now): ?ReopeningRequest;
}
