<?php

declare(strict_types=1);

namespace App\Application\Timesheet;

use App\Application\Period\PeriodModificationGuard;
use App\Domain\Project\Project;
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

        $project = $this->projects->findActive($tenant, $projectId);
        if (!$project instanceof Project) {
            throw new TimesheetException('Projet introuvable ou inactif : imputation impossible.');
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
