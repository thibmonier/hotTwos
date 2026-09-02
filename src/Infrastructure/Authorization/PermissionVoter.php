<?php

declare(strict_types=1);

namespace App\Infrastructure\Authorization;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\User\User;
use Doctrine\DBAL\Exception as DbalException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Pont entre `is_granted('<permission>')` (UI / Twig) et l'{@see Authorizer} applicatif (ARC-19).
 *
 * Permet à la navigation et aux vues de **refléter** les permissions effectives de l'utilisateur
 * sans dupliquer la logique d'autorisation. Ce voter ne remplace PAS l'enforcement : la barrière
 * de sécurité reste `Authorizer::ensureCan(...)` dans la couche applicative (ARC-106 — masquer un
 * lien n'est pas un contrôle d'accès). Il ne fait que l'affichage.
 *
 * - Ne supporte que les attributs qui correspondent à une valeur de {@see Permission} (ex.
 *   `view:project_financials`). Tout autre attribut (ex. `ROLE_USER`) est laissé aux autres voters.
 * - N'audite pas : il s'appuie sur `Authorizer::can()` (booléen pur), et non sur `ensureCan()`,
 *   afin que le simple rendu de la navigation ne génère pas d'entrées de journal de refus (HAB-6).
 *
 * @extends Voter<string, mixed>
 */
final class PermissionVoter extends Voter
{
    public function __construct(
        private readonly Authorizer $authorizer,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return Permission::tryFrom($attribute) instanceof Permission;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $permission = Permission::tryFrom($attribute);
        if (!$permission instanceof Permission) {
            return false;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        try {
            return $this->authorizer->can($user, $permission);
        } catch (DbalException) {
            // Voter d'AFFICHAGE : si les rôles ne peuvent pas être résolus (base indisponible,
            // schéma partiel), on masque l'entrée (fail-closed). L'enforcement réel reste
            // `Authorizer::ensureCan(...)` côté use case (ARC-106) — jamais cette couche UI.
            return false;
        }
    }
}
