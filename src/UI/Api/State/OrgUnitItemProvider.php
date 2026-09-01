<?php

declare(strict_types=1);

namespace App\UI\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Organization\OrgUnit;
use App\Domain\Organization\OrgUnitRepository;
use App\UI\Api\Resource\OrgUnitResource;

/**
 * Charge une unité par identifiant (US-010), cloisonnée au tenant courant. Requis par
 * l'opération DELETE (désactivation) : renvoyer `null` produit un 404 avant traitement.
 *
 * @implements ProviderInterface<OrgUnitResource>
 */
final readonly class OrgUnitItemProvider implements ProviderInterface
{
    public function __construct(
        private Authorizer $authorizer,
        private CurrentUser $currentUser,
        private OrgUnitRepository $units,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?OrgUnitResource
    {
        $user = $this->currentUser->require();
        $this->authorizer->ensureCan($user, Permission::MANAGE_ORGANIZATION);
        $id = is_string($uriVariables['id'] ?? null) ? $uriVariables['id'] : '';

        $unit = $this->units->find($user->tenantId(), $id);
        if (!$unit instanceof OrgUnit) {
            return null;
        }

        return new OrgUnitResource(
            id: $unit->id(),
            parentId: $unit->parentId(),
            name: $unit->name(),
            active: $unit->isActive(),
        );
    }
}
