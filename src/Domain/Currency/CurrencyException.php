<?php

declare(strict_types=1);

namespace App\Domain\Currency;

use RuntimeException;

/**
 * Erreur métier du référentiel devises (US-016) — traduite en 422 par le listener HTTP.
 */
class CurrencyException extends RuntimeException
{
}
