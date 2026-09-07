<?php

declare(strict_types=1);

namespace App\Domain\Budget;

/**
 * US-079b (EF-PRJ-15) — seuils de dérive de charge résolus pour un projet : seuil d'alerte (1er) et
 * seuil d'escalade direction (2e). Fournis par {@see ChargeDriftThresholdProvider}, avec repli sur les
 * constantes OBJ-2 de {@see ChargeLandingCalculator} quand aucun paramétrage n'existe pour le type.
 */
final readonly class ResolvedChargeDriftThreshold
{
    public function __construct(
        public float $alertPercent,
        public float $escalationPercent,
    ) {
    }

    public static function default(): self
    {
        return new self(
            ChargeDriftThresholdProvider::DEFAULT_ALERT_PERCENT,
            ChargeDriftThresholdProvider::DEFAULT_ESCALATION_PERCENT,
        );
    }
}
