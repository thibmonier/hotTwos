<?php

declare(strict_types=1);

namespace App\Domain\Project;

/**
 * Statut d'un engagement externe (US-034, EF-PRJ-10).
 */
enum CommitmentStatus: string
{
    case PREVISIONNEL = 'previsionnel';
    case ENGAGE = 'engage';
    case FACTURE_RECU = 'facture_recu';
    case SOLDE = 'solde';

    public function label(): string
    {
        return match ($this) {
            self::PREVISIONNEL => 'Prévisionnel',
            self::ENGAGE => 'Engagé',
            self::FACTURE_RECU => 'Facturé reçu',
            self::SOLDE => 'Soldé',
        };
    }
}
