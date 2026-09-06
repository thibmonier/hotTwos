<?php

declare(strict_types=1);

namespace App\Domain\Project;

use App\Domain\Tenant\TenantId;

interface BudgetAmendmentRepository
{
    public function save(BudgetAmendment $amendment): void;

    /**
     * Avenants d'un projet, du plus ancien au plus récent.
     *
     * @return list<BudgetAmendment>
     */
    public function findForProject(TenantId $tenant, string $projectId): array;
}
