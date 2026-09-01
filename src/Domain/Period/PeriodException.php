<?php

declare(strict_types=1);

namespace App\Domain\Period;

use RuntimeException;

/**
 * Erreur métier de gestion des périodes (US-057). Traduite en 422 côté API, sans exposer de trace.
 */
class PeriodException extends RuntimeException
{
}
