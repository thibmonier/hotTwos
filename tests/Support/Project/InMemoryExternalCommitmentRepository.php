<?php

declare(strict_types=1);

namespace App\Tests\Support\Project;

use App\Domain\Project\ExternalCommitment;
use App\Domain\Project\ExternalCommitmentRepository;
use App\Domain\Tenant\TenantId;

final class InMemoryExternalCommitmentRepository implements ExternalCommitmentRepository
{
    /** @var list<ExternalCommitment> */
    public array $commitments = [];

    public function save(ExternalCommitment $commitment): void
    {
        foreach ($this->commitments as $existing) {
            if ($existing === $commitment) {
                return;
            }
        }
        $this->commitments[] = $commitment;
    }

    public function findForProject(TenantId $tenant, string $projectId): array
    {
        return array_values(array_filter(
            $this->commitments,
            static fn (ExternalCommitment $c): bool => $c->tenantId()->equals($tenant) && $c->projectId() === $projectId,
        ));
    }
}
