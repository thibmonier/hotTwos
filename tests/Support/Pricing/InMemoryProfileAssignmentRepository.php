<?php

declare(strict_types=1);

namespace App\Tests\Support\Pricing;

use App\Domain\Pricing\ProfileAssignment;
use App\Domain\Pricing\ProfileAssignmentRepository;
use App\Domain\Tenant\TenantId;

final class InMemoryProfileAssignmentRepository implements ProfileAssignmentRepository
{
    /** @var list<ProfileAssignment> */
    public array $assignments = [];

    public function save(ProfileAssignment $assignment): void
    {
        foreach ($this->assignments as $existing) {
            if ($existing === $assignment) {
                return;
            }
        }
        $this->assignments[] = $assignment;
    }

    public function findForUser(TenantId $tenant, string $userId): array
    {
        return array_values(array_filter(
            $this->assignments,
            static fn (ProfileAssignment $a): bool => $a->tenantId()->equals($tenant) && $a->userId() === $userId,
        ));
    }
}
