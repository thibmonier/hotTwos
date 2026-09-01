<?php

declare(strict_types=1);

namespace App\Application\Pricing\Message;

use App\Application\Messaging\TenantAwareMessage;
use App\Domain\Tenant\TenantId;

/**
 * Événement de définition d'un tarif de profil (US-011 → US-060, CA-4).
 *
 * Publié sur le bus à chaque nouvelle entrée tarifaire ; consommé de façon asynchrone par la
 * valorisation pour **re-déclencher** automatiquement le calcul des imputations restées sans
 * tarif (`missing_rate`). Couplage par événement : la tarification ignore la valorisation.
 * Porteur de son tenant (ARC-47) pour le rejeu hors requête HTTP.
 */
final readonly class ProfileRateDefined implements TenantAwareMessage
{
    public function __construct(
        private string $tenantId,
        private string $profileId,
    ) {
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }

    public function profileId(): string
    {
        return $this->profileId;
    }
}
