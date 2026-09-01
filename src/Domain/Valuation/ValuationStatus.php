<?php

declare(strict_types=1);

namespace App\Domain\Valuation;

/**
 * Statut d'une valorisation d'imputation (US-060).
 *
 * - VALUED : coût et revenu calculés avec le taux en vigueur, figés ;
 * - MISSING_RATE : aucun tarif en vigueur à la date — valorisation partielle en attente (CA-4).
 */
enum ValuationStatus: string
{
    case VALUED = 'valued';
    case MISSING_RATE = 'missing_rate';
}
