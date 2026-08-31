<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Domain\User\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use LogicException;

/**
 * US-002 — points d'entrée d'authentification (login JSON) et d'introspection.
 * Le login est intercepté par le pare-feu (json_login) ; ces actions renvoient
 * l'utilisateur courant, dont le tenant a été résolu à la source (US-001).
 */
final class SecurityController extends AbstractController
{
    /**
     * Route interceptée par le pare-feu `json_login`. Le corps de méthode ne s'exécute
     * qu'en cas de succès d'authentification.
     */
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Identifiants invalides.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse($this->describe($user));
    }

    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(): never
    {
        throw new LogicException('Interceptée par le pare-feu (logout).');
    }

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(#[CurrentUser] User $user): JsonResponse
    {
        return new JsonResponse($this->describe($user));
    }

    /**
     * @return array{email: string, tenant: string, roles: list<string>}
     */
    private function describe(User $user): array
    {
        return [
            'email' => $user->getUserIdentifier(),
            'tenant' => $user->tenantId()->toString(),
            'roles' => $user->getRoles(),
        ];
    }
}
