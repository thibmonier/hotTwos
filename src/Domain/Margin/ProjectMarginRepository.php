<?php

declare(strict_types=1);

namespace App\Domain\Margin;

use App\Domain\Tenant\TenantId;

/**
 * Port de persistance des marges figées (US-071, DIP). Tenant explicite.
 *
 * L'écriture est réservée au moteur de figeage ({@see \App\Application\Margin\ComputeProjectMargins}) :
 * un figeage remplace intégralement les marges de la période visée (idempotence à la clôture / réouverture),
 * sans jamais toucher aux autres périodes (non-rétroactivité, INV-2).
 */
interface ProjectMarginRepository
{
    /**
     * Fige les marges d'une période : supprime celles déjà présentes pour (tenant, période) puis
     * enregistre les nouvelles, atomiquement. Les autres périodes ne sont jamais affectées (INV-2).
     *
     * @param list<ProjectMargin> $margins
     */
    public function replaceForPeriod(TenantId $tenant, string $period, array $margins): void;

    /**
     * Marges figées d'une période, triées du CA décroissant.
     *
     * @return list<ProjectMargin>
     */
    public function findForPeriod(TenantId $tenant, string $period): array;
}
