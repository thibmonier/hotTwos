<?php

declare(strict_types=1);

namespace App\Domain\Fec;

use RuntimeException;

/**
 * Erreur métier d'export FEC (US-074) : période non clôturée, configuration absente, période invalide.
 */
final class FecExportException extends RuntimeException
{
}
