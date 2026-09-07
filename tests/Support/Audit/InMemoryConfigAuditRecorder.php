<?php

declare(strict_types=1);

namespace App\Tests\Support\Audit;

use App\Domain\Audit\AuditAction;
use App\Domain\Audit\ConfigAuditRecorder;
use App\Domain\Tenant\TenantId;

final class InMemoryConfigAuditRecorder implements ConfigAuditRecorder
{
    /** @var list<array{objectType: string, objectLabel: string, action: AuditAction, field: ?string, before: ?string, after: ?string}> */
    public array $records = [];

    public function record(
        TenantId $tenant,
        string $actorUserId,
        AuditAction $action,
        string $objectType,
        string $objectLabel,
        ?string $field = null,
        ?string $valueBefore = null,
        ?string $valueAfter = null,
    ): void {
        $this->records[] = [
            'objectType' => $objectType,
            'objectLabel' => $objectLabel,
            'action' => $action,
            'field' => $field,
            'before' => $valueBefore,
            'after' => $valueAfter,
        ];
    }

    public function findForTenant(TenantId $tenant, ?string $objectType = null, ?string $actorUserId = null): array
    {
        return [];
    }

    public function has(string $objectType): bool
    {
        return array_any($this->records, fn (array $record): bool => $record['objectType'] === $objectType);
    }
}
