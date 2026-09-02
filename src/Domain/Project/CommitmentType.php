<?php

declare(strict_types=1);

namespace App\Domain\Project;

/**
 * Type d'engagement externe d'un projet (US-034, EF-PRJ-10).
 */
enum CommitmentType: string
{
    case SOUS_TRAITANCE = 'sous_traitance';
    case ACHAT_MATERIEL = 'achat_materiel';
    case ACHAT_LOGICIEL = 'achat_logiciel';
    case DEPLACEMENT = 'deplacement';
    case AUTRE = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::SOUS_TRAITANCE => 'Sous-traitance',
            self::ACHAT_MATERIEL => 'Achat matériel',
            self::ACHAT_LOGICIEL => 'Achat logiciel/licence',
            self::DEPLACEMENT => 'Frais de déplacement',
            self::AUTRE => 'Autre',
        };
    }
}
