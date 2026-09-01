<?php

declare(strict_types=1);

namespace App\Domain\Reminder;

use RuntimeException;

/**
 * Erreur métier du module de relances (US-056) — traduite en 422 par le listener HTTP.
 */
class ReminderException extends RuntimeException
{
}
