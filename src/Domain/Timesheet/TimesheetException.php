<?php

declare(strict_types=1);

namespace App\Domain\Timesheet;

use RuntimeException;

/**
 * Règle métier de saisie de temps violée (US-050). Le pont HTTP la traduit en 422.
 */
class TimesheetException extends RuntimeException
{
}
