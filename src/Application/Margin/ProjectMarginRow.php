<?php

declare(strict_types=1);

namespace App\Application\Margin;

/**
 * Ligne de marge d'un projet, **déjà gated** pour l'affichage (US-071, T-071-06, CA-5).
 *
 * Le CA reconnu est toujours présent ; le coût, la marge et le taux de marge sont `null` lorsque le
 * lecteur ne porte pas l'habilitation coût (HAB-1) — la couche de présentation ne décide rien, elle
 * affiche ce que le backend a autorisé.
 */
final readonly class ProjectMarginRow
{
    public function __construct(
        public string $projectRef,
        public string $projectName,
        public int $revenueCents,
        public ?int $costCents,
        public ?int $marginCents,
        public ?float $marginRatePercent,
        public bool $partial,
        public int $unvaluedCount,
    ) {
    }
}
