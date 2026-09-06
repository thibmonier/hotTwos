<?php

declare(strict_types=1);

namespace App\Domain\Currency;

/**
 * Résultat d'une conversion vers la devise de référence (US-016). `available = false` = aucun taux en
 * vigueur pour la devise à la date (pas de conversion silencieuse à 0, CA-4).
 */
final readonly class ConversionResult
{
    public function __construct(
        public ?int $referenceCents,
        public bool $available,
    ) {
    }
}
