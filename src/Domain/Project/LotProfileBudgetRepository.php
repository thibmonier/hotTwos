<?php

declare(strict_types=1);

namespace App\Domain\Project;

use App\Domain\Tenant\TenantId;

interface LotProfileBudgetRepository
{
    public function save(LotProfileBudget $line): void;

    /**
     * @return list<LotProfileBudget>
     */
    public function findForLot(TenantId $tenant, string $lotId): array;

    /**
     * Toutes les lignes des lots d'un projet (jointure lot → projet).
     *
     * @return list<LotProfileBudget>
     */
    public function findForProject(TenantId $tenant, string $projectId): array;
}
