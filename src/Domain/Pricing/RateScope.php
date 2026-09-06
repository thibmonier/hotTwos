<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

/**
 * Niveau de surcharge d'un taux de vente (US-015, EF-REF-19). Le niveau **profil** (défaut) reste porté
 * par {@see ProfileRate} ; `SellingRate` ne porte que les surcharges client/projet. Priorité de
 * résolution : PROJECT > CLIENT > profil.
 */
enum RateScope: string
{
    case CLIENT = 'client';
    case PROJECT = 'project';

    public function label(): string
    {
        return match ($this) {
            self::CLIENT => 'client',
            self::PROJECT => 'projet',
        };
    }
}
