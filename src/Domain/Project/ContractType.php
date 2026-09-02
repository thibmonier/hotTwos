<?php

declare(strict_types=1);

namespace App\Domain\Project;

/**
 * Mode de contractualisation d'un projet (US-030). Périmètre minimal ; s'enrichira avec la facturation
 * (EPIC-005).
 */
enum ContractType: string
{
    case FORFAIT = 'forfait';
    case REGIE = 'regie';

    public function label(): string
    {
        return match ($this) {
            self::FORFAIT => 'Forfait',
            self::REGIE => 'Régie',
        };
    }
}
