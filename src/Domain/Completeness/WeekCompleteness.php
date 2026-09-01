<?php

declare(strict_types=1);

namespace App\Domain\Completeness;

use DateTimeImmutable;

/**
 * Complétude d'une semaine pour un collaborateur (US-058). Objet de lecture pur.
 */
final readonly class WeekCompleteness
{
    public function __construct(
        public string $userId,
        public DateTimeImmutable $weekStart,
        public int $expectedDays,
        public int $filledDays,
        public CompletenessState $state,
    ) {
    }

    /** Taux de saisie (0..1) ; 1 si aucun jour n'était attendu (semaine entièrement absente). */
    public function rate(): float
    {
        if ($this->expectedDays <= 0) {
            return 1.0;
        }

        return min(1.0, $this->filledDays / $this->expectedDays);
    }
}
