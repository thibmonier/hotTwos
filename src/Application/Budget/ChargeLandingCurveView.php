<?php

declare(strict_types=1);

namespace App\Application\Budget;

/**
 * US-079c — série d'atterrissage d'un projet, pour l'affichage de la courbe.
 */
final readonly class ChargeLandingCurveView
{
    /**
     * @param list<ChargeLandingCurvePoint> $points ordonnés par période croissante
     */
    public function __construct(
        public string $projectId,
        public string $projectName,
        public bool $costVisible,
        public array $points,
    ) {
    }

    public function hasSeries(): bool
    {
        return count($this->points) > 0;
    }
}
