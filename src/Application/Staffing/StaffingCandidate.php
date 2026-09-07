<?php

declare(strict_types=1);

namespace App\Application\Staffing;

/**
 * US-040 — candidat au staffing : collaborateur possédant la compétence (niveau) et sa disponibilité
 * (jours) sur la période. Aucun coût (HAB-1).
 */
final readonly class StaffingCandidate
{
    public function __construct(
        public string $userId,
        public string $displayName,
        public int $skillLevel,
        public int $availableDays,
    ) {
    }
}
