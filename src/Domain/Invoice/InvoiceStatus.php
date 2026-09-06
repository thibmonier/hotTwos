<?php

declare(strict_types=1);

namespace App\Domain\Invoice;

/**
 * Statut d'une facture (US-075). Tranche minimale : seule l'émission est modélisée.
 * Les statuts « payée / annulée / avoir » relèvent d'une tranche ultérieure (ADR-0022).
 */
enum InvoiceStatus: string
{
    case ISSUED = 'issued';
}
