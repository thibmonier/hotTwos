<?php

declare(strict_types=1);

namespace App\Application\Activity;

use App\Application\Timesheet\EnsureAbsenceProject;
use App\Domain\Activity\ActivityReport;
use App\Domain\Activity\ActivityType;
use App\Domain\Activity\ProjectActivity;
use App\Domain\Tenant\TenantId;
use App\Domain\Timesheet\TimeEntry;
use App\Domain\Timesheet\TimeEntryRepository;
use App\Domain\Timesheet\ValidationStatus;
use App\Domain\Project\ProjectRepository;
use DateTimeImmutable;

/**
 * Synthèse de lecture de l'activité d'un collaborateur (US-059, EF-TMP-26/27). Répartit le temps par
 * projet et par type (production/absence) sur une période glissante alignée sur les semaines, et
 * calcule le taux d'occupation (production imputée / temps ouvré attendu). Seuls les temps VALIDÉS et
 * SOUMIS sont comptés (RG-TMP-4 : les refusés sont exclus). Aucune écriture.
 */
final readonly class ActivitySummary
{
    private const int DAILY_MINUTES = 420;

    public function __construct(
        private TimeEntryRepository $entries,
        private ProjectRepository $projects,
    ) {
    }

    public function forUser(TenantId $tenant, string $userId, DateTimeImmutable $now, int $weeks): ActivityReport
    {
        $start = $now->modify('monday this week')->modify(sprintf('-%d weeks', max(0, $weeks - 1)))->setTime(0, 0);
        $end = $now->setTime(0, 0);

        [$labels, $absenceIds] = $this->projectIndex($tenant);

        $byProjectMinutes = [];
        $production = 0;
        $absence = 0;
        foreach ($this->entries->findForUserInRange($tenant, $userId, $start, $end) as $entry) {
            if (!$this->counts($entry)) {
                continue;
            }
            $byProjectMinutes[$entry->projectId()] = ($byProjectMinutes[$entry->projectId()] ?? 0) + $entry->minutes();
            if (in_array($entry->projectId(), $absenceIds, true)) {
                $absence += $entry->minutes();
            } else {
                $production += $entry->minutes();
            }
        }

        arsort($byProjectMinutes);
        $byProject = [];
        foreach ($byProjectMinutes as $projectId => $minutes) {
            $byProject[] = new ProjectActivity($projectId, $labels[$projectId] ?? substr($projectId, 0, 8), $minutes);
        }

        return new ActivityReport(
            $start,
            $end,
            $byProject,
            $this->typeBreakdown($production, $absence),
            $production,
            $absence,
            $this->expectedMinutes($start, $end),
        );
    }

    private function counts(TimeEntry $entry): bool
    {
        return ValidationStatus::VALIDATED === $entry->status() || ValidationStatus::PENDING === $entry->status();
    }

    /**
     * @return array{0: array<string, string>, 1: list<string>} libellés par id, ids des projets « absence »
     */
    private function projectIndex(TenantId $tenant): array
    {
        $labels = [];
        $absenceIds = [];
        foreach ($this->projects->findAllActive($tenant) as $project) {
            $labels[$project->id()] = $project->name();
            if (EnsureAbsenceProject::CODE === $project->code()) {
                $absenceIds[] = $project->id();
            }
        }

        return [$labels, $absenceIds];
    }

    /**
     * @return array<string, int>
     */
    private function typeBreakdown(int $production, int $absence): array
    {
        $breakdown = [];
        if ($production > 0) {
            $breakdown[ActivityType::PRODUCTION->value] = $production;
        }
        if ($absence > 0) {
            $breakdown[ActivityType::ABSENCE->value] = $absence;
        }

        return $breakdown;
    }

    /** Temps ouvré attendu = jours ouvrés (Lun-Ven) de la période × durée journalière de référence. */
    private function expectedMinutes(DateTimeImmutable $start, DateTimeImmutable $end): int
    {
        $weekdays = 0;
        for ($day = $start; $day <= $end; $day = $day->modify('+1 day')) {
            if ((int) $day->format('N') <= 5) {
                ++$weekdays;
            }
        }

        return $weekdays * self::DAILY_MINUTES;
    }
}
