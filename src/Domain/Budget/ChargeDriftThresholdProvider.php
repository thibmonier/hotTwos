<?php

declare(strict_types=1);

namespace App\Domain\Budget;

use App\Domain\Project\ContractType;
use App\Domain\Tenant\TenantId;

/**
 * US-079b (EF-PRJ-15, DIP) — résout les seuils de dérive de charge par (tenant, type de projet).
 *
 * Les constantes par défaut reprennent la règle OBJ-2 ({@see ChargeLandingCalculator}) : ainsi l'UI et
 * l'Application ne dépendent jamais de l'Infrastructure pour le repli (le défaut est porté par le port
 * du Domaine).
 */
interface ChargeDriftThresholdProvider
{
    public const float DEFAULT_ALERT_PERCENT = ChargeLandingCalculator::OVERRUN_ALERT_PERCENT;

    /**
     * Seuil d'escalade direction par défaut (2e seuil). Paramétrable par type ; valeur de repli tant
     * qu'aucun seuil n'est configuré.
     */
    public const float DEFAULT_ESCALATION_PERCENT = 25.0;

    public function resolve(TenantId $tenant, ?ContractType $type): ResolvedChargeDriftThreshold;
}
