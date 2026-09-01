<?php

declare(strict_types=1);

namespace App\Domain\Absence;

use RuntimeException;

/**
 * Erreur métier du module absences (US-054). Traduite en 422 côté API, sans exposer de trace.
 */
class AbsenceException extends RuntimeException
{
}
