<?php

declare(strict_types=1);

namespace App\Application\Timesheet;

use App\Domain\Tenant\TenantId;
use App\Domain\Timesheet\TimesheetException;
use InvalidArgumentException;

/**
 * Enregistrement d'une semaine complète en une opération (US-051, ≤ 2 min).
 *
 * Applique chaque cellule via {@see RecordTimeEntry} (mêmes règles : projet actif, plafond
 * journalier, upsert). Best-effort par cellule : une cellule refusée n'interrompt pas le
 * lot, son motif est remonté — le contrôleur Stimulus reflète les cellules en erreur.
 */
final readonly class RecordWeek
{
    public function __construct(private RecordTimeEntry $recordTimeEntry)
    {
    }

    /**
     * @param list<WeekCell> $cells
     *
     * @return list<CellError> vide si toutes les cellules ont été enregistrées
     */
    public function record(TenantId $tenant, string $userId, array $cells): array
    {
        $errors = [];

        foreach ($cells as $cell) {
            try {
                $this->recordTimeEntry->record($tenant, $userId, $cell->projectId, $cell->date, $cell->minutes, $cell->comment);
            } catch (TimesheetException|InvalidArgumentException $exception) {
                $errors[] = new CellError($cell->projectId, $cell->date->format('Y-m-d'), $exception->getMessage());
            }
        }

        return $errors;
    }
}
