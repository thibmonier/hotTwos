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

    /**
     * Charge un lot d'imputations par identifiants (validation par lot — US-055).
     *
     * @param list<string> $ids
     *
     * @return list<TimeEntry>
     */
    public function findByIds(TenantId $tenant, array $ids): array;

    /**
     * Imputations en attente de validation sur un ensemble de projets (US-055).
     *
     * @param list<string> $projectIds
     *
     * @return list<TimeEntry>
     */
    public function findPendingForProjects(TenantId $tenant, array $projectIds): array;

    /**
     * Imputations **validées** dont le jour de prestation appartient à `[from, to)` — recalcul
     * de valorisation d'une période (US-060, CA-5). Bornes semi-ouvertes (from inclus, to exclu).
     *
     * @return list<TimeEntry>
     */
    public function findValidatedInPeriod(TenantId $tenant, DateTimeImmutable $from, DateTimeImmutable $to): array;

    /**
     * Nombre d'imputations **non validées** (soumises/refusées) dont le jour appartient à
     * `[from, to)` — avertissement de clôture (US-057, CA-3). Bornes semi-ouvertes.
     */
    public function countUnvalidatedInPeriod(TenantId $tenant, DateTimeImmutable $from, DateTimeImmutable $to): int;

    public function save(TimeEntry $entry): void;
}
