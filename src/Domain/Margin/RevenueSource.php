<?php

declare(strict_types=1);

namespace App\Domain\Margin;

use App\Domain\Tenant\TenantId;

/**
 * Source de revenu d'un (projet, période) pour le calcul de la rentabilité (US-076, ADR-0022).
 *
 * Règle **unique** (DIP/ARC-6) : le **facturé réel** s'il existe, sinon le **CA reconnu** (repli).
 * Consommée par le moteur de marge (US-071) — et, via la marge figée, par le dashboard (US-073) et
 * l'export FEC (US-074). Jamais les deux sources à la fois (pas de double comptage).
 */
interface RevenueSource
{
    /**
     * Revenu retenu (centimes) : facturé réel du (projet, période) s'il est > 0, sinon `$recognizedFallbackCents`.
     */
    public function revenueFor(TenantId $tenant, string $projectRef, string $period, int $recognizedFallbackCents): int;
}
