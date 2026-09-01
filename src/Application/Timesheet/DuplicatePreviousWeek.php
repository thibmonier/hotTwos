<?php

declare(strict_types=1);

namespace App\Application\Timesheet;

use App\Domain\Tenant\TenantId;
use App\Domain\Timesheet\TimeEntry;
use App\Domain\Timesheet\TimeEntryRepository;
use DateTimeImmutable;

/**
 * Duplique la semaine précédente dans la semaine cible (US-051, levier ≤ 2 min) : reprend
 * chaque imputation de la semaine N-1 et la reporte au même jour de la semaine cible, via
 * {@see RecordWeek} (mêmes règles : projet actif, plafond, upsert).
 */
final readonly class DuplicatePreviousWeek
{
    public function __construct(
        private TimeEntryRepository $entries,
        private RecordWeek $recordWeek,
    ) {
    }

    /**
     * @param DateTimeImmutable $targetWeekMonday lundi de la semaine cible
     *
     * @return list<CellError> vide si tout a été reporté
     */
    public function into(TenantId $tenant, string $userId, DateTimeImmutable $targetWeekMonday): array
    {
        $previousMonday = $targetWeekMonday->modify('-7 day');
        $previousSunday = $targetWeekMonday->modify('-1 day');

        $source = $this->entries->findForUserInRange($tenant, $userId, $previousMonday, $previousSunday);

        $cells = array_map(
            static fn (TimeEntry $entry): WeekCell => new WeekCell(
                $entry->projectId(),
                $entry->workDate()->modify('+7 day'),
                $entry->minutes(),
                $entry->comment(),
            ),
            $source,
        );

        return $this->recordWeek->record($tenant, $userId, $cells);
    }
}
