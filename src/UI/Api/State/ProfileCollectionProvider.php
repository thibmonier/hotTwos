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
 * Liste les profils de tarification du tenant (US-011). Réservé à l'admin (ARC-19).
 *
 * @implements ProviderInterface<ProfileResource>
 */
final readonly class ProfileCollectionProvider implements ProviderInterface
{
    public function __construct(
        private Authorizer $authorizer,
        private CurrentUser $currentUser,
        private ProfileRepository $profiles,
    ) {
    }

    /**
     * @return list<ProfileResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->currentUser->require();
        $this->authorizer->ensureCan($user, Permission::MANAGE_PRICING);

        return array_map(
            static fn (Profile $profile): ProfileResource => new ProfileResource(
                id: $profile->id(),
                name: $profile->name(),
                calculationMode: $profile->calculationMode()->value,
                active: $profile->isActive(),
            ),
            $this->profiles->findByTenant($user->tenantId()),
        );
    }
}
