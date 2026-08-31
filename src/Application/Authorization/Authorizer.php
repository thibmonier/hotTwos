<?php

declare(strict_types=1);

namespace App\Application\Authorization;

use App\Domain\Authorization\AccessDeniedException;
use App\Domain\Authorization\DataScope;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\Role;
use App\Domain\Authorization\RoleRepository;
use App\Domain\Authorization\SecurityAuditLogger;
use App\Domain\User\User;

/**
 * Point de contrôle des habilitations (ARC-19) : la vérification vit dans la couche
 * applicative, jamais dans l'UI ni dans le domaine, et n'est jamais déléguée à
 * l'interface (ARC-106 — masquer un bouton n'est pas un contrôle d'accès).
 *
 * Combine deux axes orthogonaux : la permission fonctionnelle et le périmètre de
 * données ({@see DataScope}). Chaque refus et chaque lecture sensible sont tracés
 * (HAB-6) via le {@see SecurityAuditLogger}.
 */
final readonly class Authorizer
{
    public function __construct(
        private RoleRepository $roles,
        private SecurityAuditLogger $audit,
    ) {
    }

    public function can(User $user, Permission $permission): bool
    {
        return array_any($this->rolesOf($user), fn (Role $role): bool => $role->grants($permission));
    }

    /**
     * Exige la permission ; trace et refuse sinon (CA-1, CA-2).
     */
    public function ensureCan(User $user, Permission $permission): void
    {
        if ($this->can($user, $permission)) {
            return;
        }

        $this->audit->record('unauthorized_action_attempt', $user->tenantId()->toString(), $user->getUserIdentifier(), [
            'permission' => $permission->value,
        ]);

        throw new AccessDeniedException(sprintf('Permission refusée : %s', $permission->value));
    }

    /**
     * Autorise une lecture sensible (coût, RH) puis la trace systématiquement (HAB-6, CA-3).
     */
    public function authorizeSensitiveRead(User $user, Permission $permission, string $resourceRef): void
    {
        $this->ensureCan($user, $permission);

        $this->audit->record('sensitive_data_read', $user->tenantId()->toString(), $user->getUserIdentifier(), [
            'permission' => $permission->value,
            'resource' => $resourceRef,
        ]);
    }

    /**
     * Périmètre effectif = le plus large parmi les rôles de l'utilisateur (plancher : OWN).
     */
    public function effectiveScope(User $user): DataScope
    {
        $widest = DataScope::OWN;
        foreach ($this->rolesOf($user) as $role) {
            if ($role->scope()->covers($widest)) {
                $widest = $role->scope();
            }
        }

        return $widest;
    }

    /**
     * Exige que le périmètre effectif couvre celui requis par la ressource ; trace et
     * refuse sinon (CA-5 — accès hors périmètre).
     */
    public function ensureWithinScope(User $user, DataScope $required, string $resourceRef): void
    {
        if ($this->effectiveScope($user)->covers($required)) {
            return;
        }

        $this->audit->record('out_of_scope_access_attempt', $user->tenantId()->toString(), $user->getUserIdentifier(), [
            'resource' => $resourceRef,
            'required_scope' => $required->value,
        ]);

        throw new AccessDeniedException('Ressource hors périmètre autorisé');
    }

    /**
     * Anti-élévation de privilège (CA-6) : on ne peut accorder qu'un rôle dont le
     * périmètre est couvert par le sien. Trace et refuse sinon.
     */
    public function ensureCanGrant(User $author, Role $role): void
    {
        $authorScope = $this->effectiveScope($author);
        if ($authorScope->covers($role->scope())) {
            return;
        }

        $this->audit->record('privilege_escalation_attempt', $author->tenantId()->toString(), $author->getUserIdentifier(), [
            'granted_role' => $role->name(),
            'granted_scope' => $role->scope()->value,
            'author_scope' => $authorScope->value,
        ]);

        throw new AccessDeniedException(sprintf('Attribution impossible : le périmètre du rôle accordé (%s) excède le périmètre autorisé de l\'auteur (%s)', $role->scope()->value, $authorScope->value));
    }

    /**
     * @return list<Role>
     */
    private function rolesOf(User $user): array
    {
        return $this->roles->findByNames($user->tenantId(), $user->getRoles());
    }
}
