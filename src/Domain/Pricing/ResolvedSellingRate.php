<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

/**
 * Taux de vente retenu (US-015) : le montant en centimes et le **niveau** appliqué (projet/client/profil),
 * affiché à l'utilisateur pour la traçabilité du chiffrage.
 */
final readonly class ResolvedSellingRate
{
    public function __construct(
        public int $sellingPriceCents,
        /** « projet », « client » ou « profil ». */
        public string $level,
    ) {
    }
}
