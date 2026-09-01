<?php

declare(strict_types=1);

namespace App\Application\Absence\Message;

use App\Application\Messaging\TenantAwareMessage;
use App\Domain\Tenant\TenantId;

/**
 * Notification de décision sur une demande d'absence (US-054, CA-1/CA-5) : consommée de façon
 * asynchrone pour notifier le demandeur du résultat (validée/refusée). Porteur de son tenant (ARC-47).
 */
final readonly class AbsenceDecided implements TenantAwareMessage
{
    public function __construct(
        private string $tenantId,
        private string $requestId,
        private bool $approved,
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

    public function isApproved(): bool
    {
        return $this->approved;
    }
}
