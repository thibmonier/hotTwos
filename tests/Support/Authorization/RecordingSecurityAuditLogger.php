<?php

declare(strict_types=1);

namespace App\Tests\Support\Authorization;

use App\Domain\Authorization\SecurityAuditLogger;

/**
 * Double de test du {@see SecurityAuditLogger} : conserve les événements tracés
 * pour assertion (qui, quoi, contexte).
 */
final class RecordingSecurityAuditLogger implements SecurityAuditLogger
{
    /** @var list<array{event: string, tenantId: string, actorId: string|null, context: array<string, string>}> */
    public array $events = [];

    public function record(string $event, string $tenantId, ?string $actorId, array $context = []): void
    {
        $this->events[] = [
            'event' => $event,
            'tenantId' => $tenantId,
            'actorId' => $actorId,
            'context' => $context,
        ];
    }

    public function has(string $event): bool
    {
        return array_any($this->events, fn (array $recorded): bool => $recorded['event'] === $event);
    }
}
