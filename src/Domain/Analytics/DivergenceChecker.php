<?php

declare(strict_types=1);

namespace App\Domain\Analytics;

use App\Domain\Tenant\TenantId;

/**
 * Port de non-divergence (US-005, ARC-119) : compare les agrégats du modèle en étoile
 * au recalcul indépendant depuis la source (le flux d'événements). Fondement du job CI
 * bloquant (CA-2) et de la réconciliation périodique (CA-3, Sprint 2).
 */
interface DivergenceChecker
{
    /**
     * @return list<Divergence> vide si le modèle est fidèle à la source
     */
    public function check(TenantId $tenant): array;
}
