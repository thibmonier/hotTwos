<?php

declare(strict_types=1);

namespace App\Infrastructure\Budget;

use App\Domain\Budget\ChargeDriftThreshold;
use App\Domain\Budget\ChargeDriftThresholdProvider;
use App\Domain\Budget\ChargeDriftThresholdRepository;
use App\Domain\Budget\ResolvedChargeDriftThreshold;
use App\Domain\Project\ContractType;
use App\Domain\Tenant\TenantId;

/**
 * US-079b (EF-PRJ-15) — résout les seuils de dérive de charge configurés par (tenant, type de projet),
 * avec repli sur les constantes OBJ-2 quand aucun paramétrage n'existe (ou type inconnu).
 */
final readonly class TenantChargeDriftThresholdProvider implements ChargeDriftThresholdProvider
{
    public function __construct(private ChargeDriftThresholdRepository $thresholds)
    {
    }

    public function resolve(TenantId $tenant, ?ContractType $type): ResolvedChargeDriftThreshold
    {
        if (!$type instanceof ContractType) {
            return ResolvedChargeDriftThreshold::default();
        }

        $configured = $this->thresholds->findFor($tenant, $type);

        return $configured instanceof ChargeDriftThreshold
            ? $configured->resolved()
            : ResolvedChargeDriftThreshold::default();
    }
}
