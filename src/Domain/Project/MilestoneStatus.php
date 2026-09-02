<?php

declare(strict_types=1);

namespace App\Domain\Project;

/**
 * Statut d'un jalon de projet (US-031, EF-PRJ-3).
 */
enum MilestoneStatus: string
{
    case A_VENIR = 'a_venir';
    case ATTEINT = 'atteint';
    case RETARDE = 'retarde';

    public function label(): string
    {
        return match ($this) {
            self::A_VENIR => 'À venir',
            self::ATTEINT => 'Atteint',
            self::RETARDE => 'Retardé',
        };
    }
}
