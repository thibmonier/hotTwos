<?php

declare(strict_types=1);

namespace App\Tests\Support\Absence;

use App\Domain\Absence\AbsenceRequest;
use App\Domain\Absence\AbsenceRequestRepository;
use App\Domain\Absence\AbsenceStatus;
use App\Domain\Tenant\TenantId;
use DateTimeImmutable;

final class InMemoryAbsenceRequestRepository implements AbsenceRequestRepository
{
    /** @var list<AbsenceRequest> */
    public array $requests = [];

    public function save(AbsenceRequest $request): void
    {
        foreach ($this->requests as $existing) {
            if ($existing === $request) {
                return;
            }
        }
        $this->requests[] = $request;
    }

    public function findById(TenantId $tenant, string $id): ?AbsenceRequest
    {
        foreach ($this->requests as $request) {
            if ($request->tenantId()->equals($tenant) && $request->id() === $id) {
                return $request;
            }
        }

        return null;
    }

    public function findForUser(TenantId $tenant, string $userId): array
    {
        return array_values(array_filter(
            $this->requests,
            static fn (AbsenceRequest $r): bool => $r->tenantId()->equals($tenant) && $r->userId() === $userId,
        ));
    }

    public function findValidatedCovering(TenantId $tenant, string $userId, DateTimeImmutable $day): ?AbsenceRequest
    {
        foreach ($this->requests as $request) {
            if ($request->tenantId()->equals($tenant)
                && $request->userId() === $userId
                && AbsenceStatus::VALIDATED === $request->status()
                && $request->coversDay($day)) {
                return $request;
            }
        }

        return null;
    }

    public function findValidatedOverlapping(TenantId $tenant, string $userId, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $fromKey = $from->format('Y-m-d');
        $toKey = $to->format('Y-m-d');

        return array_values(array_filter($this->requests, static fn (AbsenceRequest $r): bool => $r->tenantId()->equals($tenant)
            && $r->userId() === $userId
            && AbsenceStatus::VALIDATED === $r->status()
            && $r->startDate()->format('Y-m-d') <= $toKey
            && $r->endDate()->format('Y-m-d') >= $fromKey));
    }
}
