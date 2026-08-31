<?php

declare(strict_types=1);

namespace App\UI\Api\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\UI\Api\State\StatusProvider;

/**
 * DTO exposé par l'API (ADR-4 / ARC-18) — jamais une entité de persistance.
 * Alimenté par {@see StatusProvider}, lui-même adossé à un cas d'usage.
 */
#[ApiResource(
    shortName: 'Status',
    operations: [new Get(uriTemplate: '/status')],
    provider: StatusProvider::class,
)]
final class StatusResource
{
    public function __construct(
        public string $status,
        public string $app,
    ) {
    }
}
