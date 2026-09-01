<?php

declare(strict_types=1);

namespace App\UI\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Organization\ConfigureOrgHierarchy;
use App\UI\Api\Resource\OrgUnitResource;

/**
 * Crée une unité via le cas d'usage (US-010). L'habilitation ADMIN et la validation métier
 * sont portées par ConfigureOrgHierarchy (403/422 via les listeners).
 *
 * @implements ProcessorInterface<OrgUnitResource, OrgUnitResource>
 */
final readonly class CreateOrgUnitProcessor implements ProcessorInterface
{
    public function __construct(
        private ConfigureOrgHierarchy $configure,
        private CurrentUser $currentUser,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): OrgUnitResource
    {
        $user = $this->currentUser->require();

        $id = $this->configure->createUnit($user->tenantId(), $user, $data->parentId, $data->name);

        return new OrgUnitResource(id: $id, parentId: $data->parentId, name: $data->name, active: true);
    }
}
