<?php

declare(strict_types=1);

namespace App\Domain\Authorization;

/**
 * Permissions fonctionnelles vérifiées côté serveur (ARC-19, ARC-106).
 *
 * Le périmètre couvert par le Walking Skeleton reste volontairement restreint aux
 * permissions mobilisées par les critères d'acceptation d'US-003 (HAB-1, HAB-6) ;
 * il s'étend module par module à mesure que le métier atterrit. `view:collaborator_cost`
 * est la permission la plus sensible de la matrice (donnée RH — HAB-1/HAB-6).
 */
enum Permission: string
{
    case VIEW_PROJECT = 'view:project';
    case CREATE_PROJECT = 'create:project';
    case EDIT_PROJECT = 'edit:project';
    case DELETE_PROJECT = 'delete:project';
    case VIEW_COLLABORATOR = 'view:collaborator';

    /** Lecture du coût journalier d'un collaborateur — donnée RH sensible (HAB-1, HAB-6). */
    case VIEW_COLLABORATOR_COST = 'view:collaborator_cost';

    /** Attribution de rôles à d'autres utilisateurs (soumise à l'anti-élévation — CA-6). */
    case MANAGE_ROLES = 'manage:roles';

    /** Paramétrage de l'organisation : hiérarchie et rattachements des collaborateurs (US-010, admin tenant). */
    case MANAGE_ORGANIZATION = 'manage:organization';

    /** Paramétrage de la tarification : profils, coûts de revient et taux de vente (US-011, admin tenant). */
    case MANAGE_PRICING = 'manage:pricing';

    /** Validation/refus des imputations de temps (chef de projet, sur ses projets — US-055). */
    case VALIDATE_TIME = 'validate:time';

    /** Recalcul manuel de la valorisation d'une période (admin/contrôle de gestion — US-060, CA-5). */
    case RECOMPUTE_VALUATION = 'recompute:valuation';

    /** Consultation du tableau de bord financier projet — CA, marge, occupation (US-060, T-060-06). */
    case VIEW_PROJECT_FINANCIALS = 'view:project_financials';

    /** Clôture d'une période et approbation des réouvertures (administrateur tenant — US-057). */
    case MANAGE_PERIODS = 'manage:periods';

    /** Demande de réouverture d'une période clôturée (chef de projet habilité — US-057). */
    case REQUEST_PERIOD_REOPENING = 'request:period_reopening';
}
