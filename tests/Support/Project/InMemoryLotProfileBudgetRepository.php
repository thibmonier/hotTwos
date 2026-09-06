<?php

declare(strict_types=1);

namespace App\Tests\Support\Project;

use App\Domain\Project\LotProfileBudget;
use App\Domain\Project\LotProfileBudgetRepository;
use App\Domain\Tenant\TenantId;

final class InMemoryLotProfileBudgetRepository implements LotProfileBudgetRepository
{
    /** @var list<LotProfileBudget> */
    public array $lines = [];

    /** @var array<string, string> lotId => projectId (pour findForProject) */
    public array $lotToProject = [];

    public function save(LotProfileBudget $line): void
    {
        foreach ($this->lines as $existing) {
            if ($existing === $line) {
                return;
            }
        }
        $this->lines[] = $line;
    }

    public function findForLot(TenantId $tenant, string $lotId): array
    {
        return array_values(array_filter(
            $this->lines,
            static fn (LotProfileBudget $l): bool => $l->tenantId()->equals($tenant) && $l->lotId() === $lotId,
        ));
    }

    public function findForProject(TenantId $tenant, string $projectId): array
    {
        return array_values(array_filter(
            $this->lines,
            fn (LotProfileBudget $l): bool => $l->tenantId()->equals($tenant) && ($this->lotToProject[$l->lotId()] ?? null) === $projectId,
        ));
    }
}
