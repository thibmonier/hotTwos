<?php

declare(strict_types=1);

namespace App\Domain\Margin;

/**
 * Moteur de marge — **unique et testé** (US-071, CA-3 / ARC-6).
 *
 * Marge réelle = produit facturable (CA reconnu, ADR-0020) − charge valorisée. Montants en centimes
 * entiers (INV-2, pas d'arithmétique flottante sur la monnaie). Aucun écran ne réplique cette
 * formule : la marge et le taux de marge sont fournis exclusivement par ce service côté backend.
 */
final class MarginCalculator
{
    /**
     * Marge en centimes = CA reconnu − coût valorisé (peut être négative).
     */
    public function marginCents(int $revenueCents, int $costCents): int
    {
        return $revenueCents - $costCents;
    }

    /**
     * Taux de marge en pourcentage (marge / CA reconnu), arrondi au centième.
     *
     * Borne CA = 0 (CA-3) : sans CA reconnu, le taux n'a pas de sens → `null` (jamais de division
     * par zéro, jamais 0 % trompeur pour un projet sans produit).
     */
    public function marginRatePercent(int $revenueCents, int $costCents): ?float
    {
        if ($revenueCents <= 0) {
            return null;
        }

        return round($this->marginCents($revenueCents, $costCents) / $revenueCents * 100, 2);
    }
}
