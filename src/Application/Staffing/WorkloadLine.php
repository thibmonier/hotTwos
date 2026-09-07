<?php

declare(strict_types=1);

namespace App\Application\Staffing;

/**
 * US-041 — ligne du plan de charge d'un collaborateur sur la période : capacité (jours ouvrés nets),
 * charge ferme (jours affectés), disponible, surcharge. Aucun coût (HAB-1).
 */
final readonly class WorkloadLine
{
    public function __construct(
        public string $userId,
        public string $displayName,
        public int $capacityDays,
        public int $firmLoadDays,
    ) {
    }

    public function availableDays(): int
    {
        return max(0, $this->capacityDays - $this->firmLoadDays);
    }

    public function isOverloaded(): bool
    {
        return $this->firmLoadDays > $this->capacityDays;
    }
}
