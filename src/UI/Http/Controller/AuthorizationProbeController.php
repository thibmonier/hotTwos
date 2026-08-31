<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\User\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * US-003 — sonde d'habilitation prouvant le mécanisme de bout en bout (ARC-19, ARC-106) :
 * le contrôle est fait côté serveur, dans la couche applicative, indépendamment de l'UI.
 *
 * À l'image de la sonde d'isolation d'US-001, ces points d'entrée valident la charpente
 * RBAC ; ils seront remplacés par les endpoints métier réels (coût, projet…) qui
 * réutiliseront le même {@see Authorizer}.
 */
final class AuthorizationProbeController extends AbstractController
{
    public function __construct(private readonly Authorizer $authorizer)
    {
    }

    /**
     * Lecture d'une donnée sensible (coût collaborateur — HAB-1) : 200 si la permission
     * est accordée (et lecture tracée — HAB-6), 403 sinon.
     */
    #[Route('/api/_probe/collaborator-cost', name: 'probe_collaborator_cost', methods: ['GET'])]
    public function collaboratorCost(#[CurrentUser] User $user): JsonResponse
    {
        $this->authorizer->authorizeSensitiveRead($user, Permission::VIEW_COLLABORATOR_COST, 'collaborator:probe');

        return new JsonResponse([
            'granted' => true,
            'permission' => Permission::VIEW_COLLABORATOR_COST->value,
            'scope' => $this->authorizer->effectiveScope($user)->value,
        ]);
    }

    /**
     * Action fonctionnelle protégée (suppression de projet) : 403 si la permission manque,
     * même si l'UI l'aurait masquée (ARC-106).
     */
    #[Route('/api/_probe/delete-project', name: 'probe_delete_project', methods: ['POST'])]
    public function deleteProject(#[CurrentUser] User $user): JsonResponse
    {
        $this->authorizer->ensureCan($user, Permission::DELETE_PROJECT);

        return new JsonResponse(['granted' => true, 'permission' => Permission::DELETE_PROJECT->value]);
    }
}
