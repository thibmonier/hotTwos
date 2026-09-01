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
 * Liste les unités organisationnelles du tenant (US-010). Réservé à l'admin (ARC-19).
 *
 * @implements ProviderInterface<OrgUnitResource>
 */
final readonly class OrgUnitCollectionProvider implements ProviderInterface
{
    public function __construct(
        private Authorizer $authorizer,
        private CurrentUser $currentUser,
        private OrgUnitRepository $units,
    ) {
    }

    /**
     * @return list<OrgUnitResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->currentUser->require();
        $this->authorizer->ensureCan($user, Permission::MANAGE_ORGANIZATION);

        return array_map(
            static fn (OrgUnit $unit): OrgUnitResource => new OrgUnitResource(
                id: $unit->id(),
                parentId: $unit->parentId(),
                name: $unit->name(),
                active: $unit->isActive(),
            ),
            $this->units->findByTenant($user->tenantId()),
        );
    }
}
