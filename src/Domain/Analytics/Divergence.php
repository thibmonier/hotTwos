<?php

declare(strict_types=1);

namespace App\Domain\Analytics;

/**
 * Écart constaté entre le modèle analytique et le recalcul depuis la source (US-005,
 * ARC-119). Porte le périmètre exact de la divergence pour le rapport CI et l'alerte
 * de réconciliation : indicateur, période, valeur attendue (source) et obtenue (modèle).
 */
final readonly class Divergence
{
    /**
     * @param non-empty-string $indicator
     * @param non-empty-string $period
     */
    public function __construct(
        public string $indicator,
        public string $period,
        public int $expectedCents,
        public int $actualCents,
    ) {
    }

    public function deltaCents(): int
    {
        return $this->actualCents - $this->expectedCents;
    }

    /**
     * Écart relatif à la valeur source, en fraction (0.001 = 0,1 %). Renvoie 0.0 quand la
     * valeur attendue est nulle et l'obtenue aussi ; sinon 1.0 (divergence totale).
     */
    public function relativeDelta(): float
    {
        if (0 === $this->expectedCents) {
            return 0 === $this->actualCents ? 0.0 : 1.0;
        }

        return abs($this->deltaCents()) / abs($this->expectedCents);
    }
}
