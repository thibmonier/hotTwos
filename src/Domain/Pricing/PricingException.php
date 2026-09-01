<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

use RuntimeException;

/**
 * Erreur métier de la tarification (US-011) : chevauchement, valeur invalide, tarif manquant.
 */
class PricingException extends RuntimeException
{
}
