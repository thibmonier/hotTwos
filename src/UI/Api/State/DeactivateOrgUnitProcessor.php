<?php

declare(strict_types=1);

namespace App\UI\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Organization\ConfigureOrgHierarchy;
use App\UI\Api\Resource\OrgUnitResource;

/**
 * `DELETE /org-units/{id}` — désactive l'unité (RG-REF-1), jamais de suppression dure (US-010).
 *
 * @implements ProcessorInterface<OrgUnitResource, null>
 */
final readonly class DeactivateOrgUnitProcessor implements ProcessorInterface
{
    public function __construct(
        private ConfigureOrgHierarchy $configure,
        private CurrentUser $currentUser,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        $user = $this->currentUser->require();
        $id = is_string($uriVariables['id'] ?? null) ? $uriVariables['id'] : '';

        $this->configure->deactivateUnit($user->tenantId(), $user, $id);

        return null;
    }
}
