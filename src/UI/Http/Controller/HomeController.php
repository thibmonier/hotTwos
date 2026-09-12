<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Absence\AbsenceBalance;
use App\Application\Activity\ActivitySummary;
use App\Application\Authorization\Authorizer;
use App\Application\Completeness\CompletenessGrid;
use App\Domain\Authorization\Permission;
use App\Domain\Pricing\ProfileRepository;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectRepository;
use App\Domain\Timesheet\TimeEntry;
use App\Domain\Timesheet\TimeEntryRepository;
use App\Domain\User\User;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use DateTimeImmutable;

final class HomeController extends AbstractController
{
    private const int RECENT_DAYS = 14;
    private const int RECENT_LIMIT = 5;
    private const int COMPLETENESS_WEEKS = 4;
    private const int DAILY_TARGET_MINUTES = 420;

    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly ProfileRepository $profiles,
        private readonly ProjectRepository $projects,
        private readonly TimeEntryRepository $timeEntries,
        private readonly ActivitySummary $activitySummary,
        private readonly CompletenessGrid $completenessGrid,
        private readonly AbsenceBalance $absenceBalance,
        private readonly ClockInterface $clock,
    ) {
    }

    #[Route('/', name: 'home', methods: ['GET'])]
    public function __invoke(#[CurrentUser] ?User $user): Response
    {
        // US-088 : point d'entrée par profil. Un collaborateur (non habilité à créer des projets)
        // arrive sur son tableau de bord ; les profils admin/manager (et l'accès anonyme) gardent
        // la home historique + la checklist de mise en route.
        if ($user instanceof User && !$this->authorizer->can($user, Permission::CREATE_PROJECT)) {
            return $this->render('home/dashboard.html.twig', $this->collaboratorDashboard($user));
        }

        return $this->render('home/index.html.twig', [
            'appName' => 'HotOnes',
            'onboarding' => $this->onboardingChecklist($user),
        ]);
    }

    /**
     * US-088 (DSH-COLLAB) — view model du tableau de bord collaborateur (P1) : contrepartie visible
     * (heures de la semaine, complétude perso, solde de congés, projets récents), imputations récentes
     * et accès rapides. Aucune logique métier ici (ARC-15) : orchestration de services existants.
     *
     * @return array<string, mixed>
     */
    private function collaboratorDashboard(User $user): array
    {
        $tenant = $user->tenantId();
        $now = $this->clock->now();
        $monday = $now->modify('monday this week');
        $sunday = $monday->modify('+6 day');

        // Heures de la semaine en cours + objectif (jours ouvrés × 7 h).
        $weekEntries = $this->timeEntries->findForUserInRange($tenant, $user->id(), $monday, $sunday);
        $weekMinutes = array_sum(array_map(static fn (TimeEntry $e): int => $e->minutes(), $weekEntries));
        $workingDays = 0;
        for ($offset = 0; $offset < 7; ++$offset) {
            if ((int) $monday->modify(sprintf('+%d day', $offset))->format('N') < 6) {
                ++$workingDays;
            }
        }
        $targetMinutes = $workingDays * self::DAILY_TARGET_MINUTES;

        // Complétude personnelle moyenne sur les dernières semaines.
        $weeks = $this->completenessGrid->build($tenant, [$user->id()], $now, self::COMPLETENESS_WEEKS);
        $completenessPercent = $this->averageCompleteness($weeks);

        // Solde de congés.
        $counters = $this->absenceBalance->for($tenant, $user->id());

        // Projets récents (activité des dernières semaines).
        $report = $this->activitySummary->forUser($tenant, $user->id(), $now, self::COMPLETENESS_WEEKS);

        return [
            'userEmail' => $user->email(),
            'todayLabel' => $this->dateLabel($now),
            'weekNumber' => (int) $now->format('W'),
            'weekMinutes' => $weekMinutes,
            'weekTargetMinutes' => $targetMinutes,
            'weekPercent' => $targetMinutes > 0 ? (int) min(100, round($weekMinutes / $targetMinutes * 100)) : 0,
            'completenessPercent' => $completenessPercent,
            'leaveBalance' => $counters->balance(),
            'leaveProjected' => $counters->projectedBalance(),
            'activeProjectsCount' => count($report->byProject),
            'recent' => $this->recentEntries($tenant, $user, $now),
        ];
    }

    /**
     * @param list<\App\Domain\Completeness\WeekCompleteness> $weeks
     */
    private function averageCompleteness(array $weeks): int
    {
        if ([] === $weeks) {
            return 0;
        }

        $sum = 0.0;
        foreach ($weeks as $week) {
            $sum += $week->rate();
        }

        return (int) round($sum / count($weeks) * 100);
    }

    /**
     * Dernières imputations (libellé projet lisible, jamais d'identifiant technique — F1).
     *
     * @return list<array{project: string, code: string, date: string, minutes: int}>
     */
    private function recentEntries(\App\Domain\Tenant\TenantId $tenant, User $user, DateTimeImmutable $now): array
    {
        $from = $now->modify(sprintf('-%d day', self::RECENT_DAYS - 1));
        $entries = $this->timeEntries->findForUserInRange($tenant, $user->id(), $from, $now);

        /** @var array<string, array{name: string, code: string}> $names */
        $names = [];
        foreach ($this->projects->findAllActive($tenant) as $project) {
            /* @var Project $project */
            $names[$project->id()] = ['name' => $project->name(), 'code' => $project->code()];
        }

        usort($entries, static fn (TimeEntry $a, TimeEntry $b): int => $b->workDate() <=> $a->workDate());

        $recent = [];
        foreach (array_slice($entries, 0, self::RECENT_LIMIT) as $entry) {
            $meta = $names[$entry->projectId()] ?? ['name' => 'Projet', 'code' => ''];
            $recent[] = [
                'project' => $meta['name'],
                'code' => $meta['code'],
                'date' => $entry->workDate()->format('d/m'),
                'minutes' => $entry->minutes(),
            ];
        }

        return $recent;
    }

    private function dateLabel(DateTimeImmutable $day): string
    {
        $days = ['Mon' => 'Lundi', 'Tue' => 'Mardi', 'Wed' => 'Mercredi', 'Thu' => 'Jeudi', 'Fri' => 'Vendredi', 'Sat' => 'Samedi', 'Sun' => 'Dimanche'];
        $months = ['January' => 'janvier', 'February' => 'février', 'March' => 'mars', 'April' => 'avril', 'May' => 'mai', 'June' => 'juin', 'July' => 'juillet', 'August' => 'août', 'September' => 'septembre', 'October' => 'octobre', 'November' => 'novembre', 'December' => 'décembre'];

        return sprintf('%s %d %s %s', $days[$day->format('D')] ?? $day->format('D'), (int) $day->format('j'), $months[$day->format('F')] ?? $day->format('F'), $day->format('Y'));
    }

    /**
     * US-019 (EF-REF-29, CA-3) — checklist de mise en route, affichée tant qu'elle n'est pas complète,
     * pour un utilisateur habilité à créer des projets. `null` sinon (pas de bandeau).
     *
     * @return array{profileReady: bool, projectCreated: bool, timeSubmitted: bool, complete: bool}|null
     */
    private function onboardingChecklist(?User $user): ?array
    {
        if (!$user instanceof User || !$this->authorizer->can($user, Permission::CREATE_PROJECT)) {
            return null;
        }

        $tenant = $user->tenantId();
        $profileReady = [] !== $this->profiles->findByTenant($tenant);
        $projectCreated = $this->projects->countByTenant($tenant) > 0;
        $timeSubmitted = $this->timeEntries->countByTenant($tenant) > 0;
        $complete = $profileReady && $projectCreated && $timeSubmitted;

        if ($complete) {
            return null;
        }

        return [
            'profileReady' => $profileReady,
            'projectCreated' => $projectCreated,
            'timeSubmitted' => $timeSubmitted,
            'complete' => false,
        ];
    }
}
