<?php

declare(strict_types=1);

namespace App\UI\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Pricing\ManageProfiles;
use App\UI\Api\Resource\ProfileResource;

/**
 * `DELETE /profiles/{id}` — désactive le profil (RG-REF-1), jamais de suppression dure (US-011).
 *
 * @implements ProcessorInterface<ProfileResource, null>
 */
final readonly class DeactivateProfileProcessor implements ProcessorInterface
{
    public function __construct(
        private ManageProfiles $manageProfiles,
        private CurrentUser $currentUser,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        $user = $this->currentUser->require();
        $id = is_string($uriVariables['id'] ?? null) ? $uriVariables['id'] : '';

        $this->manageProfiles->deactivate($user->tenantId(), $user, $id);

        return null;
    }
}
