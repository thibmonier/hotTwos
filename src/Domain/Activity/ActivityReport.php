<?php

declare(strict_types=1);

namespace App\Domain\Activity;

use DateTimeImmutable;

/**
 * Synthèse d'activité d'un collaborateur sur une période glissante (US-059, EF-TMP-26/27). Objet de
 * lecture pur : répartition du temps par projet et par type (production/absence) et taux d'occupation
 * (temps de production imputé vs temps ouvré attendu). Seuls les temps VALIDÉS et SOUMIS sont comptés
 * (RG-TMP-4) ; le calcul est fait par le service applicatif.
 */
final readonly class ActivityReport
{
    /**
     * @param list<ProjectActivity> $byProject trié par minutes décroissantes
     * @param array<string, int>    $byType    type (valeur d'ActivityType) => minutes
     */
    public function __construct(
        public DateTimeImmutable $periodStart,
        public DateTimeImmutable $periodEnd,
        public array $byProject,
        public array $byType,
        public int $productionMinutes,
        public int $absenceMinutes,
        public int $expectedMinutes,
    ) {
    }

    public function totalMinutes(): int
    {
        return $this->productionMinutes + $this->absenceMinutes;
    }

    /** Taux d'occupation (production imputée / temps ouvré attendu), borné à 0 si rien n'est attendu. */
    public function occupationRate(): float
    {
        if ($this->expectedMinutes <= 0) {
            return 0.0;
        }

        return $this->productionMinutes / $this->expectedMinutes;
    }

    public function isEmpty(): bool
    {
        return [] === $this->byProject;
    }
}
