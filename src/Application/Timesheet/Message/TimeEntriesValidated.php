<?php

declare(strict_types=1);

namespace App\Application\Timesheet\Message;

use App\Application\Messaging\TenantAwareMessage;
use App\Domain\Tenant\TenantId;
use DateTimeImmutable;

/**
 * Événement de validation d'imputations (US-055 → US-060) : publié sur le bus à la validation,
 * consommé de façon asynchrone pour valoriser les temps (couplage par événement, pas par appel
 * direct). Porteur de son tenant (ARC-47) pour le rejeu hors requête HTTP.
 */
final readonly class TimeEntriesValidated implements TenantAwareMessage
{
    /**
     * @param list<string> $timeEntryIds
     */
    public function __construct(
        private string $tenantId,
        private array $timeEntryIds,
        private DateTimeImmutable $validatedAt,
    ) {
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }

    /**
     * @return list<string>
     */
    public function timeEntryIds(): array
    {
        return $this->timeEntryIds;
    }

    public function validatedAt(): DateTimeImmutable
    {
        return $this->validatedAt;
    }
}
