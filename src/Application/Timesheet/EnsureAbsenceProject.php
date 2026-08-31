<?php

declare(strict_types=1);

namespace App\Application\Timesheet;

use App\Domain\Project\Project;
use App\Domain\Project\ProjectRepository;
use App\Domain\Tenant\TenantId;

/**
 * Garantit l'existence du projet système « Absence » d'un tenant (US-051, absences dans la
 * grille). Minimal au Sprint 3 : une ligne de saisie dédiée. La gestion complète (types,
 * compteurs, validation — US-054) est ultérieure.
 */
final readonly class EnsureAbsenceProject
{
    public const string CODE = 'ABSENCE';

    public function __construct(private ProjectRepository $projects)
    {
    }

    public function forTenant(TenantId $tenant): void
    {
        foreach ($this->projects->findAllActive($tenant) as $project) {
            if (self::CODE === $project->code()) {
                return;
            }
        }

        $this->projects->save(new Project($tenant, self::CODE, 'Absence'));
    }
}
