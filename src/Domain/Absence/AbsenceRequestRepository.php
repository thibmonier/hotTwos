<?php

declare(strict_types=1);

namespace App\Domain\Absence;

use App\Domain\Tenant\TenantId;
use DateTimeImmutable;

/**
 * Port de persistance des demandes d'absence (US-054, DIP). Tenant explicite.
 */
interface AbsenceRequestRepository
{
    public function save(AbsenceRequest $request): void;

    public function findById(TenantId $tenant, string $id): ?AbsenceRequest;

    /**
     * Toutes les demandes d'un collaborateur (compteurs — EF-TMP-16), plus récentes d'abord.
     *
     * @return list<AbsenceRequest>
     */
    public function findForUser(TenantId $tenant, string $userId): array;

    /**
     * Absence **validée** couvrant un jour donné pour un collaborateur (blocage d'imputation —
     * RG-TMP-3). `null` si le jour est libre.
     */
    public function findValidatedCovering(TenantId $tenant, string $userId, DateTimeImmutable $day): ?AbsenceRequest;

    /**
     * Absences **validées** d'un collaborateur chevauchant `[from, to]` — déduction des jours
     * attendus dans le calcul de complétude (US-058).
     *
     * @return list<AbsenceRequest>
     */
    public function findValidatedOverlapping(TenantId $tenant, string $userId, DateTimeImmutable $from, DateTimeImmutable $to): array;
}
