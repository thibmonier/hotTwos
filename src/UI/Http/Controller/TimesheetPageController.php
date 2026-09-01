<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Activity\ActivitySummary;
use App\Application\Reminder\ReminderBanner;
use App\Application\Timesheet\EnsureAbsenceProject;
use App\Domain\Activity\ActivityReport;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectRepository;
use App\Domain\Timesheet\TimeEntryRepository;
use App\Domain\User\User;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use DateTimeImmutable;

/**
 * US-050 — écran de saisie hebdomadaire (adaptateur web, ADR-5 : rendu serveur Twig).
 * Aucune logique métier ici (ARC-15) : la page présente les projets actifs et les
 * imputations de la semaine du collaborateur authentifié ; l'enregistrement passe par
 * l'API `POST /api/time-entries` (contrôleur Stimulus).
 */
final class TimesheetPageController extends AbstractController
{
    private const int SUMMARY_WEEKS = 4;
    private const int SUMMARY_TOP_PROJECTS = 7;

    public function __construct(
        private readonly ProjectRepository $projects,
        private readonly TimeEntryRepository $entries,
        private readonly EnsureAbsenceProject $ensureAbsenceProject,
        private readonly ReminderBanner $reminderBanner,
        private readonly ActivitySummary $activitySummary,
        private readonly ClockInterface $clock,
    ) {
    }

    #[Route('/saisie', name: 'timesheet_week', methods: ['GET'])]
    public function week(#[CurrentUser] User $user, Request $request): Response
    {
        $reference = $this->referenceDate($request->query->get('date'));
        $monday = $reference->modify('monday this week');

        /** @var list<array{date: string, label: string}> $days */
        $days = [];
        for ($offset = 0; $offset < 7; ++$offset) {
            $day = $monday->modify(sprintf('+%d day', $offset));
            $days[] = ['date' => $day->format('Y-m-d'), 'label' => $day->format('D d/m')];
        }
        $sunday = $monday->modify('+6 day');

        // Ligne « Absence » disponible dans la grille (US-051).
        $this->ensureAbsenceProject->forTenant($user->tenantId());

        $recorded = $this->entries->findForUserInRange($user->tenantId(), $user->id(), $monday, $sunday);

        /** @var array<string, array<string, int>> $grid projectId => (Y-m-d => minutes) */
        $grid = [];
        foreach ($recorded as $entry) {
            $grid[$entry->projectId()][$entry->workDate()->format('Y-m-d')] = $entry->minutes();
        }

        $projects = array_map(
            static fn (Project $project): array => ['id' => $project->id(), 'code' => $project->code(), 'name' => $project->name()],
            $this->projects->findAllActive($user->tenantId()),
        );

        $reminderLateWeeks = $this->reminderBanner->lateWeeksForOptedOut($user);
        $summary = $this->activitySummary->forUser($user->tenantId(), $user->id(), $this->clock->now(), self::SUMMARY_WEEKS);

        return $this->render('timesheet/week.html.twig', [
            'days' => $days,
            'projects' => $projects,
            'grid' => $grid,
            'reminderLateWeeks' => $reminderLateWeeks > 0 ? $reminderLateWeeks : null,
            'summary' => $this->summaryView($summary),
            'weekStart' => $monday->format('Y-m-d'),
            'weekLabel' => sprintf('Semaine du %s au %s', $monday->format('d/m/Y'), $sunday->format('d/m/Y')),
            'prevDate' => $monday->modify('-7 day')->format('Y-m-d'),
            'nextDate' => $monday->modify('+7 day')->format('Y-m-d'),
        ]);
    }

    /**
     * Vue de la synthèse d'activité pour le panneau « Ma synthèse » (US-059) : top projets + « Autres »
     * agrégés, parts en % et durées formatées, prêtes à l'affichage.
     *
     * @return array<string, mixed>
     */
    private function summaryView(ActivityReport $report): array
    {
        $total = $report->totalMinutes();
        $projects = [];
        $others = 0;
        $othersCount = 0;
        foreach ($report->byProject as $index => $activity) {
            if ($index < self::SUMMARY_TOP_PROJECTS) {
                $projects[] = [
                    'label' => $activity->label,
                    'duration' => $this->minutesLabel($activity->minutes),
                    'percent' => $this->percent($activity->minutes, $total),
                ];
            } else {
                $others += $activity->minutes;
                ++$othersCount;
            }
        }

        return [
            'empty' => $report->isEmpty(),
            'periodStart' => $report->periodStart->format('d/m/Y'),
            'periodEnd' => $report->periodEnd->format('d/m/Y'),
            'occupationPercent' => (int) round($report->occupationRate() * 100),
            'productionDuration' => $this->minutesLabel($report->productionMinutes),
            'productionPercent' => $this->percent($report->productionMinutes, $total),
            'absenceDuration' => $this->minutesLabel($report->absenceMinutes),
            'absencePercent' => $this->percent($report->absenceMinutes, $total),
            'projects' => $projects,
            'othersDuration' => $this->minutesLabel($others),
            'othersPercent' => $this->percent($others, $total),
            'othersCount' => $othersCount,
        ];
    }

    private function percent(int $minutes, int $total): int
    {
        return $total > 0 ? (int) round($minutes / $total * 100) : 0;
    }

    private function minutesLabel(int $minutes): string
    {
        $rest = $minutes % 60;

        return 0 === $rest ? sprintf('%dh', intdiv($minutes, 60)) : sprintf('%dh%02d', intdiv($minutes, 60), $rest);
    }

    private function referenceDate(mixed $raw): DateTimeImmutable
    {
        if (is_string($raw)) {
            $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $raw);
            if (false !== $parsed) {
                return $parsed;
            }
        }

        return new DateTimeImmutable('today');
    }
}
