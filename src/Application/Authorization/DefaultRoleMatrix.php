<?php

declare(strict_types=1);

namespace App\Application\Authorization;

use App\Domain\Authorization\DataScope;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\RoleDefinition;

/**
 * Matrice de rôles de référence du CDC (US-003, CA-4) : reproductible par paramétrage,
 * source unique de vérité pour l'initialisation d'un tenant.
 *
 * Elle traduit les personas P1..P6 et les invariants d'habilitation. En particulier,
 * le « Chef de projet » n'a jamais `view:collaborator_cost` (HAB-1) ; seuls les rôles
 * à périmètre large (Resource Manager, Dirigeant) accèdent au coût, avec traçage (HAB-6).
 *
 * Le périmètre couvert reste celui du Walking Skeleton ; il s'enrichit module par module.
 */
final class DefaultRoleMatrix
{
    /**
     * @return list<RoleDefinition>
     */
    public static function definitions(): array
    {
        return [
            new RoleDefinition('Collaborateur', [
                Permission::VIEW_PROJECT,
                Permission::VIEW_COLLABORATOR,
            ], DataScope::OWN),

            new RoleDefinition('Chef de projet', [
                Permission::VIEW_PROJECT,
                Permission::CREATE_PROJECT,
                Permission::EDIT_PROJECT,
                Permission::VIEW_COLLABORATOR,
                Permission::VALIDATE_TIME,
                Permission::VIEW_PROJECT_FINANCIALS,
                Permission::REQUEST_PERIOD_REOPENING,
                Permission::VALIDATE_ABSENCE,
                Permission::VIEW_TEAM_COMPLETENESS,
                Permission::MANAGE_REMINDERS,
            ], DataScope::OWN_PROJECTS),

            new RoleDefinition('Resource Manager', [
                Permission::VIEW_PROJECT,
                Permission::VIEW_COLLABORATOR,
                Permission::VIEW_COLLABORATOR_COST,
                Permission::VIEW_PROJECT_FINANCIALS,
                Permission::VIEW_TEAM_COMPLETENESS,
            ], DataScope::POOL),

            new RoleDefinition('Dirigeant', [
                Permission::VIEW_PROJECT,
                Permission::VIEW_COLLABORATOR,
                Permission::VIEW_COLLABORATOR_COST,
                Permission::VIEW_PROJECT_FINANCIALS,
                Permission::VIEW_TEAM_COMPLETENESS,
            ], DataScope::TENANT),

            new RoleDefinition('Administrateur', [
                Permission::MANAGE_ROLES,
                Permission::MANAGE_ORGANIZATION,
                Permission::MANAGE_PRICING,
                Permission::VIEW_PROJECT,
                Permission::CREATE_PROJECT,
                Permission::EDIT_PROJECT,
                Permission::DELETE_PROJECT,
                Permission::VIEW_COLLABORATOR,
                Permission::VALIDATE_TIME,
                Permission::RECOMPUTE_VALUATION,
                Permission::VIEW_PROJECT_FINANCIALS,
                Permission::MANAGE_PERIODS,
                Permission::REQUEST_PERIOD_REOPENING,
                Permission::VALIDATE_ABSENCE,
                Permission::VIEW_TEAM_COMPLETENESS,
                Permission::MANAGE_REMINDERS,
            ], DataScope::TENANT),
        ];
    }
}
