<?php

declare(strict_types=1);

namespace App\UI\Api\State;

use App\Domain\Authorization\AccessDeniedException;
use App\Domain\User\User;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Résout l'utilisateur authentifié pour les opérations API (US-010). Centralise le garde
 * « authentification requise » afin que providers et processors restent concis (DRY).
 */
final readonly class CurrentUser
{
    public function __construct(private Security $security)
    {
    }

    public function require(): User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedException('Authentification requise.');
        }

        return $user;
    }
}
