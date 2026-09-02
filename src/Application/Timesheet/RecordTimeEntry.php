<?php

declare(strict_types=1);

namespace App\Application\Timesheet;

use App\Application\Period\PeriodModificationGuard;
use App\Domain\Absence\AbsenceRequest;
use App\Domain\Absence\AbsenceRequestRepository;
use App\Domain\Project\ExceptionalImputationOpeningRepository;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectAssignmentRepository;
use App\Domain\Project\ProjectReopeningRepository;
use App\Domain\Project\ProjectRepository;
use App\Domain\Tenant\TenantId;
use App\Domain\Timesheet\TimeEntry;
use App\Domain\Timesheet\TimeEntryRepository;
use App\Domain\Timesheet\TimesheetException;
use DateTimeImmutable;

/**
 * Cas d'usage de saisie d'une imputation (US-050, ARC-19). Applique les règles métier
 * côté serveur : projet actif du tenant, plafond journalier, et upsert au grain
 * (utilisateur, projet, jour). L'appelant fournit l'utilisateur agissant — un collaborateur
 * ne saisit que pour lui-même (garanti par le contrôleur via l'utilisateur authentifié).
 */
final readonly class RecordTimeEntry
{
    /** Plafond de temps imputable sur une journée, tous projets confondus (24 h). */
    public const int DAILY_CAP_MINUTES = 1440;

    public function __construct(
        private ProjectRepository $projects,
        private TimeEntryRepository $entries,
        private PeriodModificationGuard $periodGuard,
        private AbsenceRequestRepository $absences,
        private ProjectAssignmentRepository $assignments,
        private ExceptionalImputationOpeningRepository $openings,
        private ProjectReopeningRepository $reopenings,
    ) {
    }

    public function record(
        TenantId $tenant,
        string $userId,
        string $projectId,
        DateTimeImmutable $workDate,
        int $minutes,
        ?string $comment = null,
    ): void {
        // Verrou de clôture (US-057, CA-4) : aucune saisie/révision sur une période clôturée (423).
        $this->periodGuard->ensureModifiable($tenant, $userId, $workDate);

        // Absence validée (US-054, RG-TMP-3) : pas d'imputation de production ce jour-là (422).
        if ($this->absences->findValidatedCovering($tenant, $userId, $workDate) instanceof AbsenceRequest) {
            throw new TimesheetException(sprintf('Impossible d\'imputer du temps de production sur une période d\'absence validée (%s).', $workDate->format('d/m/Y')));
        }

        $project = $this->projects->findActive($tenant, $projectId);
        if (!$project instanceof Project) {
            throw new TimesheetException('Projet introuvable ou inactif : imputation impossible.');
        }

        // Cycle de vie du projet (US-030, CA-2) : l'imputation n'est ouverte qu'« En cours ».
        // Le projet système « Absence » est « En cours » par défaut : il reste imputable.
        // Exception (US-038, CA-3/CA-7) : un projet clôturé redevient imputable pendant une fenêtre de
        // réouverture formelle approuvée par un ADMIN (4-eyes).
        if (!$project->allowsImputation()) {
            if ($project->isClosed() && $this->reopenings->hasActiveOn($tenant, $projectId, $workDate)) {
                // Réouverture active : imputation autorisée sur la fenêtre.
            } elseif ($project->isClosed()) {
                throw new TimesheetException('Imputations fermées : projet clôturé — une réouverture formelle validée par un administrateur est requise (RG-TMP-6).');
            } else {
                throw new TimesheetException(sprintf('Imputations non autorisées : projet « %s ».', $project->status()->label()));
            }
        }

        // Affectation (US-037, CA-1) : dès qu'un projet a des affectations, seuls les collaborateurs
        // affectés (ou bénéficiant d'une ouverture exceptionnelle sur la semaine) peuvent imputer.
        // Un projet sans affectation reste ouvert (rétro-compatibilité — le projet « Absence » inclus).
        if ($this->assignments->hasAssignments($tenant, $projectId)
            && !$this->assignments->isAssignedOn($tenant, $projectId, $userId, $workDate)
            && !$this->openings->coversDay($tenant, $projectId, $userId, $workDate)) {
            throw new TimesheetException('Imputation non autorisée : vous n\'êtes pas affecté à ce projet.');
        }

        $otherProjectsMinutes = $this->entries->minutesLoggedForDay($tenant, $userId, $workDate, $projectId);
        if ($otherProjectsMinutes + $minutes > self::DAILY_CAP_MINUTES) {
            throw new TimesheetException(sprintf('Plafond journalier dépassé : %d min déjà imputées, %d demandées (max %d).', $otherProjectsMinutes, $minutes, self::DAILY_CAP_MINUTES));
        }

        $existing = $this->entries->findForDay($tenant, $userId, $projectId, $workDate);
        if ($existing instanceof TimeEntry) {
            $existing->reviseTo($minutes, $comment);
            $this->entries->save($existing);

            return;
        }

        $this->entries->save(new TimeEntry($tenant, $userId, $projectId, $workDate, $minutes, $comment));
    }
}
