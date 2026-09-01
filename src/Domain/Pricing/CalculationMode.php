<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

/**
 * Mode de calcul du coût de revient d'un profil (US-011, EF-REF-20).
 *
 * - DIRECT : coût direct (salaire brut chargé rapporté aux jours ouvrés) ;
 * - LOADED : coût « chargé » (brut × (1 + taux de charge), rapporté aux jours ouvrés — CA-2) ;
 * - FULL   : coût « complet » (chargé + frais de structure alloués).
 *
 * Le mode paramètre la formule de calcul ; le taux de vente reste une saisie indépendante.
 */
enum CalculationMode: string
{
    case DIRECT = 'direct';
    case LOADED = 'loaded';
    case FULL = 'full';
}
