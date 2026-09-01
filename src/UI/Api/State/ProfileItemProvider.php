<?php

declare(strict_types=1);

namespace App\UI\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Pricing\Profile;
use App\Domain\Pricing\ProfileRepository;
use App\UI\Api\Resource\ProfileResource;

/**
 * Charge un profil par identifiant (US-011), cloisonné au tenant, réservé à l'admin. Requis par
 * l'opération DELETE (désactivation) : `null` produit un 404 avant traitement.
 *
 * @implements ProviderInterface<ProfileResource>
 */
final readonly class ProfileItemProvider implements ProviderInterface
{
    public function __construct(
        private Authorizer $authorizer,
        private CurrentUser $currentUser,
        private ProfileRepository $profiles,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?ProfileResource
    {
        $user = $this->currentUser->require();
        $this->authorizer->ensureCan($user, Permission::MANAGE_PRICING);
        $id = is_string($uriVariables['id'] ?? null) ? $uriVariables['id'] : '';

        $profile = $this->profiles->find($user->tenantId(), $id);
        if (!$profile instanceof Profile) {
            return null;
        }

        return new ProfileResource(
            id: $profile->id(),
            name: $profile->name(),
            calculationMode: $profile->calculationMode()->value,
            active: $profile->isActive(),
        );
    }
}
