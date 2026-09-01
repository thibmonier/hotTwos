<?php

declare(strict_types=1);

namespace App\Tests\Support\Organization;

use App\Domain\Organization\OrgMembership;
use App\Domain\Organization\OrgMembershipRepository;
use App\Domain\Tenant\TenantId;

final class InMemoryOrgMembershipRepository implements OrgMembershipRepository
{
    /** @var list<OrgMembership> */
    public array $memberships = [];

    public function save(OrgMembership $membership): void
    {
        foreach ($this->memberships as $existing) {
            if ($existing === $membership) {
                return;
            }
        }
        $this->memberships[] = $membership;
    }

    public function findForUser(TenantId $tenant, string $userId): array
    {
        return array_values(array_filter(
            $this->memberships,
            static fn (OrgMembership $m): bool => $m->tenantId()->equals($tenant) && $m->userId() === $userId,
        ));
    }

    public function findForOrgUnit(TenantId $tenant, string $orgUnitId): array
    {
        return array_values(array_filter(
            $this->memberships,
            static fn (OrgMembership $m): bool => $m->tenantId()->equals($tenant) && $m->orgUnitId() === $orgUnitId,
        ));
    }
}
