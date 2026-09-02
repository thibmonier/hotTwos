<?php

declare(strict_types=1);

namespace App\Tests\Support\Project;

use App\Domain\Project\ExceptionalImputationOpening;
use App\Domain\Project\ExceptionalImputationOpeningRepository;
use App\Domain\Tenant\TenantId;
use DateTimeImmutable;

final class InMemoryExceptionalImputationOpeningRepository implements ExceptionalImputationOpeningRepository
{
    /** @var list<ExceptionalImputationOpening> */
    public array $openings = [];

    public function save(ExceptionalImputationOpening $opening): void
    {
        foreach ($this->openings as $existing) {
            if ($existing === $opening) {
                return;
            }
        }
        $this->openings[] = $opening;
    }

    public function coversDay(TenantId $tenant, string $projectId, string $userId, DateTimeImmutable $day): bool
    {
        return array_any($this->openings, fn (ExceptionalImputationOpening $o): bool => $o->tenantId()->equals($tenant) && $o->projectId() === $projectId && $o->userId() === $userId && $o->coversDay($day));
    }

    public function findForProject(TenantId $tenant, string $projectId): array
    {
        return array_values(array_filter(
            $this->openings,
            static fn (ExceptionalImputationOpening $o): bool => $o->tenantId()->equals($tenant) && $o->projectId() === $projectId,
        ));
    }
}
