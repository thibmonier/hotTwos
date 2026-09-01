<?php

declare(strict_types=1);

namespace App\Application\Completeness;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\User\User;
use App\Domain\User\UserRepository;

/**
 * Résolution du périmètre de la grille de complétude (US-058, CA-5).
 *
 * Un collaborateur voit **uniquement lui-même** ; le périmètre « équipe » exige
 * `VIEW_TEAM_COMPLETENESS` (403 sinon). Périmètre équipe = tous les collaborateurs du tenant
 * (simplification — un vrai périmètre managérial/BU viendra avec la hiérarchie US-010).
 */
final readonly class CompletenessScope
{
    public function __construct(
        private Authorizer $authorizer,
        private UserRepository $users,
    ) {
    }

    /**
     * @return list<string> identifiants des collaborateurs du périmètre demandé
     */
    public function resolve(User $user, bool $teamScope): array
    {
        if (!$teamScope) {
            return [$user->id()];
        }

        $this->authorizer->ensureCan($user, Permission::VIEW_TEAM_COMPLETENESS);

        return $this->users->findIdsByTenant($user->tenantId());
    }
}
