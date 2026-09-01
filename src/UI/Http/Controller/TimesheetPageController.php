<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Reminder\ReminderBanner;
use App\Application\Timesheet\EnsureAbsenceProject;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectRepository;
use App\Domain\Timesheet\TimeEntryRepository;
use App\Domain\User\User;
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
    public function __construct(
        private readonly ProjectRepository $projects,
        private readonly TimeEntryRepository $entries,
        private readonly EnsureAbsenceProject $ensureAbsenceProject,
        private readonly ReminderBanner $reminderBanner,
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

        return $this->render('timesheet/week.html.twig', [
            'days' => $days,
            'projects' => $projects,
            'grid' => $grid,
            'reminderLateWeeks' => $reminderLateWeeks > 0 ? $reminderLateWeeks : null,
            'weekStart' => $monday->format('Y-m-d'),
            'weekLabel' => sprintf('Semaine du %s au %s', $monday->format('d/m/Y'), $sunday->format('d/m/Y')),
            'prevDate' => $monday->modify('-7 day')->format('Y-m-d'),
            'nextDate' => $monday->modify('+7 day')->format('Y-m-d'),
        ]);
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
