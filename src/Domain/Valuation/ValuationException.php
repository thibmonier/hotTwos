<?php

declare(strict_types=1);

namespace App\Domain\Valuation;

use RuntimeException;

/**
 * Erreur métier de valorisation (US-060). Traduite en 422 côté API, sans exposer de trace.
 */
class ValuationException extends RuntimeException
{
}
