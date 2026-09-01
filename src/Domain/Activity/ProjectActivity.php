<?php

declare(strict_types=1);

namespace App\Domain\Activity;

/**
 * Part d'activité d'un collaborateur sur un projet, sur la période de synthèse (US-059). Lecture pure.
 */
final readonly class ProjectActivity
{
    public function __construct(
        public string $projectId,
        public string $label,
        public int $minutes,
    ) {
    }
}
