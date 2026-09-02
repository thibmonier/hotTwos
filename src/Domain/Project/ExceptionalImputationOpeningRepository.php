<?php

declare(strict_types=1);

namespace App\Domain\Project;

use App\Domain\Tenant\TenantId;
use DateTimeImmutable;

/**
 * Port de persistance des ouvertures exceptionnelles d'imputation (US-037, DIP). Tenant explicite.
 */
interface ExceptionalImputationOpeningRepository
{
    public function save(ExceptionalImputationOpening $opening): void;

    /** Une ouverture couvre-t-elle ce jour pour ce (projet, collaborateur) ? (CA-2) */
    public function coversDay(TenantId $tenant, string $projectId, string $userId, DateTimeImmutable $day): bool;

    /**
     * Ouvertures d'un projet, plus récentes d'abord (affichage « ouvertures actives »).
     *
     * @return list<ExceptionalImputationOpening>
     */
    public function findForProject(TenantId $tenant, string $projectId): array;
}
