<?php

declare(strict_types=1);

namespace App\Domain\Invoice;

use RuntimeException;

/**
 * Erreur métier d'émission de facture (US-075) : période invalide/non clôturée, projet introuvable,
 * montant invalide.
 */
final class InvoiceException extends RuntimeException
{
}
