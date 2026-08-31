<?php

declare(strict_types=1);

namespace App\UI\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\Health\HealthCheck;
use App\UI\Api\Resource\StatusResource;

/**
 * State provider sur mesure (ADR-4) : traduit le cas d'usage applicatif en DTO.
 *
 * @implements ProviderInterface<StatusResource>
 */
final readonly class StatusProvider implements ProviderInterface
{
    public function __construct(
        private HealthCheck $healthCheck,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): StatusResource
    {
        $status = $this->healthCheck->status();

        return new StatusResource(
            status: $status['status'],
            app: $status['app'],
        );
    }
}
