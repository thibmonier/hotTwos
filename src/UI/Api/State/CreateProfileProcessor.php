<?php

declare(strict_types=1);

namespace App\UI\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Pricing\ManageProfiles;
use App\Domain\Pricing\CalculationMode;
use App\Domain\Pricing\PricingException;
use App\UI\Api\Resource\ProfileResource;

/**
 * Crée un profil via le cas d'usage (US-011). Traduit le mode de calcul (chaîne) en énumération
 * du domaine ; habilitation et règles portées par ManageProfiles (403/422 via listeners).
 *
 * @implements ProcessorInterface<ProfileResource, ProfileResource>
 */
final readonly class CreateProfileProcessor implements ProcessorInterface
{
    public function __construct(
        private ManageProfiles $manageProfiles,
        private CurrentUser $currentUser,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ProfileResource
    {
        $user = $this->currentUser->require();

        $mode = CalculationMode::tryFrom($data->calculationMode);
        if (null === $mode) {
            throw new PricingException(sprintf('Mode de calcul invalide : %s (attendu direct/loaded/full).', $data->calculationMode));
        }

        $id = $this->manageProfiles->create($user->tenantId(), $user, $data->name, $mode);

        return new ProfileResource(id: $id, name: $data->name, calculationMode: $mode->value, active: true);
    }
}
