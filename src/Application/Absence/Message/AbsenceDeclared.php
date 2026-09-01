<?php

declare(strict_types=1);

namespace App\Application\Absence\Message;

use App\Application\Messaging\TenantAwareMessage;
use App\Domain\Tenant\TenantId;

/**
 * Notification de dépôt d'une demande d'absence (US-054, CA-1) : consommée de façon asynchrone
 * pour notifier le manager (email + in-app). Porteur de son tenant (ARC-47).
 */
final readonly class AbsenceDeclared implements TenantAwareMessage
{
    public function __construct(
        private string $tenantId,
        private string $requestId,
    ) {
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }

    public function requestId(): string
    {
        return $this->requestId;
    }
}
