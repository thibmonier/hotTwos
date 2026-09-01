<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Timesheet\EnsureAbsenceProject;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectRepository;
use App\Domain\Timesheet\TimeEntryRepository;
use App\Domain\User\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use DateTimeImmutable;

/**
 * US-052 — vue de **saisie quotidienne mobile-first** (adaptateur web responsive). Alternative à la
 * grille hebdomadaire desktop (`/saisie`) pour le collaborateur en déplacement : un jour, une liste de
 * cartes projet. Aucune logique métier ici (ARC-15) ni nouvelle route serveur : la soumission réutilise
 * l'API de saisie US-050 (`/api/time-entries/week`). La navigation jour et l'offline sont côté Stimulus.
 */
final class TimesheetDayController extends AbstractController
{
    /** Abréviations françaises (le format natif reste anglophone) — libellé « Mar 25/08 ». */
    private const array DAY_ABBR = ['Mon' => 'Lun', 'Tue' => 'Mar', 'Wed' => 'Mer', 'Thu' => 'Jeu', 'Fri' => 'Ven', 'Sat' => 'Sam', 'Sun' => 'Dim'];

    public function __construct(
        private readonly ProjectRepository $projects,
        private readonly TimeEntryRepository $entries,
        private readonly EnsureAbsenceProject $ensureAbsenceProject,
    ) {
    }

    #[Route('/saisie/jour', name: 'timesheet_day_today', methods: ['GET'])]
    #[Route('/saisie/jour/{date}', name: 'timesheet_day', requirements: ['date' => '\d{4}-\d{2}-\d{2}'], methods: ['GET'])]
    public function day(#[CurrentUser] User $user, ?string $date = null): Response
    {
        $day = $this->parseDate($date);
        $previous = $day->modify('-1 day');

        $this->ensureAbsenceProject->forTenant($user->tenantId());

        $projects = array_map(
            static fn (Project $project): array => ['id' => $project->id(), 'code' => $project->code(), 'name' => $project->name()],
            $this->projects->findAllActive($user->tenantId()),
        );

        // Une seule requête pour le jour ET la veille (source de la reprise), découpée en mémoire.
        $entries = $this->entries->findForUserInRange($user->tenantId(), $user->id(), $previous, $day);
        $today = $day->format('Y-m-d');
        $minutesByProject = [];
        $previousMinutesByProject = [];
        foreach ($entries as $entry) {
            if ($entry->workDate()->format('Y-m-d') === $today) {
                $minutesByProject[$entry->projectId()] = $entry->minutes();
            } else {
                $previousMinutesByProject[$entry->projectId()] = $entry->minutes();
            }
        }

        return $this->render('timesheet/day.html.twig', [
            'date' => $today,
            'dayLabel' => $this->label($day),
            'weekNumber' => (int) $day->format('W'),
            'prevDate' => $previous->format('Y-m-d'),
            'nextDate' => $day->modify('+1 day')->format('Y-m-d'),
            'todayDate' => new DateTimeImmutable('today')->format('Y-m-d'),
            'projects' => $projects,
            'minutesByProject' => $minutesByProject,
            'previousMinutesByProject' => $previousMinutesByProject,
        ]);
    }

    private function label(DateTimeImmutable $day): string
    {
        return sprintf('%s %s', self::DAY_ABBR[$day->format('D')] ?? $day->format('D'), $day->format('d/m'));
    }

    private function parseDate(?string $raw): DateTimeImmutable
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
