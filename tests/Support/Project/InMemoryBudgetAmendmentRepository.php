<?php

declare(strict_types=1);

namespace App\Tests\Support\Project;

use App\Domain\Project\BudgetAmendment;
use App\Domain\Project\BudgetAmendmentRepository;
use App\Domain\Tenant\TenantId;

final class InMemoryBudgetAmendmentRepository implements BudgetAmendmentRepository
{
    /** @var list<BudgetAmendment> */
    public array $amendments = [];

    public function save(BudgetAmendment $amendment): void
    {
        $this->amendments[] = $amendment;
    }

    public function findForProject(TenantId $tenant, string $projectId): array
    {
        return array_values(array_filter(
            $this->amendments,
            static fn (BudgetAmendment $a): bool => $a->tenantId()->equals($tenant) && $a->projectId() === $projectId,
        ));
    }
}
