<?php

declare(strict_types=1);

namespace App\Domain\Budget;

use App\Domain\Tenant\TenantId;

interface ChargeLandingSnapshotRepository
{
    /**
     * Remplace l'ensemble des snapshots d'une période (idempotence par (tenant, projet, période) :
     * une re-clôture remplace, n'ajoute pas).
     *
     * @param list<ChargeLandingSnapshot> $snapshots
     */
    public function replaceForPeriod(TenantId $tenant, string $period, array $snapshots): void;

    /**
     * Série d'un projet, ordonnée par période croissante (pour la courbe).
     *
     * @return list<ChargeLandingSnapshot>
     */
    public function findForProject(TenantId $tenant, string $projectId): array;
}
