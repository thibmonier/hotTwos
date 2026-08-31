<?php

declare(strict_types=1);

namespace App\Application\Timesheet;

use DateTimeImmutable;

/**
 * Une cellule de la grille de saisie hebdomadaire (US-051) : un projet, un jour, une durée.
 */
final readonly class WeekCell
{
    public function __construct(
        public string $projectId,
        public DateTimeImmutable $date,
        public int $minutes,
        public ?string $comment = null,
    ) {
    }
}
