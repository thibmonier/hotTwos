<?php

declare(strict_types=1);

namespace App\Domain\Timesheet;

use App\Domain\Tenant\TenantId;
use DateTimeImmutable;

/**
 * Port de persistance des imputations (US-050, DIP). Implémentation Doctrine en
 * infrastructure. Cloisonnement par tenant exprimé explicitement.
 */
interface TimeEntryRepository
{
    public function findForDay(TenantId $tenant, string $userId, string $projectId, DateTimeImmutable $workDate): ?TimeEntry;

    /**
     * Total des minutes déjà imputées par l'utilisateur ce jour-là, tous projets confondus,
     * en excluant éventuellement un projet (celui en cours de re-saisie).
     */
    public function minutesLoggedForDay(TenantId $tenant, string $userId, DateTimeImmutable $workDate, ?string $exceptProjectId = null): int;

    /**
     * Imputations d'un utilisateur sur une plage de jours (bornes incluses).
     *
     * @return list<TimeEntry>
     */
    public function findForUserInRange(TenantId $tenant, string $userId, DateTimeImmutable $from, DateTimeImmutable $to): array;

    public function save(TimeEntry $entry): void;
}
