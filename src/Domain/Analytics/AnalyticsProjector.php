<?php

declare(strict_types=1);

namespace App\Domain\Analytics;

use App\Domain\Tenant\TenantId;

/**
 * Port du projecteur analytique (ADR-9, ARC-111, ARC-114) : reconstruit le modèle en
 * étoile d'un tenant en rejouant son flux d'événements.
 *
 * Seul composant autorisé à écrire dans les tables de faits (ARC-111). La reconstruction
 * est idempotente et par tenant (aucun impact sur les autres — ARC-114).
 */
interface AnalyticsProjector
{
    public function rebuild(TenantId $tenant): void;
}
