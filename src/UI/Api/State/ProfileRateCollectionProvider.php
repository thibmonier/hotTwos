<?php

declare(strict_types=1);

namespace App\UI\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Pricing\ProfileRate;
use App\Domain\Pricing\ProfileRateRepository;
use App\UI\Api\Resource\ProfileRateResource;

/**
 * Historique tarifaire d'un profil (US-011) : `GET /profile-rates?profileId=…`, admin (ARC-19).
 *
 * @implements ProviderInterface<ProfileRateResource>
 */
final readonly class ProfileRateCollectionProvider implements ProviderInterface
{
    public function __construct(
        private Authorizer $authorizer,
        private CurrentUser $currentUser,
        private ProfileRateRepository $rates,
    ) {
    }

    /**
     * @return list<ProfileRateResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->currentUser->require();

        $filters = $context['filters'] ?? [];
        $profileId = is_array($filters) && is_string($filters['profileId'] ?? null) ? $filters['profileId'] : '';
        if ('' === $profileId) {
            $this->authorizer->ensureCan($user, Permission::MANAGE_PRICING);

            return [];
        }

        // HAB-6 : la lecture du coût de revient (donnée sensible) est tracée, comme le coût collaborateur.
        $this->authorizer->authorizeSensitiveRead($user, Permission::MANAGE_PRICING, 'profile_rate:'.$profileId);

        return array_map(
            static fn (ProfileRate $rate): ProfileRateResource => new ProfileRateResource(
                id: $rate->id(),
                profileId: $rate->profileId(),
                effectiveFrom: $rate->period()->from()->format('Y-m-d'),
                effectiveTo: $rate->period()->to()?->format('Y-m-d'),
                costPriceCents: $rate->costPriceCents(),
                sellingPriceCents: $rate->sellingPriceCents(),
            ),
            $this->rates->findForProfile($user->tenantId(), $profileId),
        );
    }
}
