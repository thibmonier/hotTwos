<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

/**
 * Aucun tarif en vigueur pour un profil à une date donnée (US-011). Consommée par la
 * valorisation (US-060, CA-4 « taux manquant ») pour déclencher une valorisation partielle.
 */
final class NoEffectiveRateException extends PricingException
{
}
