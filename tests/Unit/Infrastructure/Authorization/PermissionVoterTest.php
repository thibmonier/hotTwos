<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Authorization;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\DataScope;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\Role;
use App\Domain\Authorization\RoleRepository;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Infrastructure\Authorization\PermissionVoter;
use App\Tests\Support\Authorization\InMemoryRoleRepository;
use App\Tests\Support\Authorization\RecordingSecurityAuditLogger;
use Doctrine\DBAL\Exception as DbalException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use RuntimeException;

/**
 * US-063 — le voter relie `is_granted('<permission>')` (nav/vues) à l'Authorizer applicatif,
 * pour l'AFFICHAGE uniquement (l'enforcement reste `ensureCan` côté use case, ARC-106).
 */
final class PermissionVoterTest extends TestCase
{
    private TenantId $tenant;
    private PermissionVoter $voter;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();

        $roles = new InMemoryRoleRepository();
        $roles->add(new Role($this->tenant, 'Contrôle de gestion', [
            Permission::VIEW_PROJECT_FINANCIALS,
        ], DataScope::TENANT));
        $roles->add(new Role($this->tenant, 'Collaborateur', [
            Permission::VIEW_PROJECT,
        ], DataScope::OWN));

        $this->voter = new PermissionVoter(new Authorizer($roles, new RecordingSecurityAuditLogger()));
    }

    public function testGrantsWhenUserHasThePermission(): void
    {
        $token = $this->tokenFor($this->user(['Contrôle de gestion']));

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, null, ['view:project_financials']),
        );
    }

    public function testDeniesWhenUserLacksThePermission(): void
    {
        $token = $this->tokenFor($this->user(['Collaborateur']));

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, null, ['view:project_financials']),
        );
    }

    public function testAbstainsOnNonPermissionAttribute(): void
    {
        $token = $this->tokenFor($this->user(['Contrôle de gestion']));

        // 'ROLE_USER' n'est pas une valeur de Permission → laissé aux autres voters.
        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, null, ['ROLE_USER']),
        );
    }

    public function testDeniesWhenNoAuthenticatedUser(): void
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, null, ['view:project_financials']),
        );
    }

    public function testDeniesWhenRoleResolutionFails(): void
    {
        // Résolution des rôles indisponible (base absente / schéma partiel) : le voter d'affichage
        // masque l'entrée (fail-closed) au lieu de propager une erreur qui casserait le rendu.
        $throwingRoles = new class () implements RoleRepository {
            /**
             * @param list<string> $names
             *
             * @return list<Role>
             */
            public function findByNames(TenantId $tenant, array $names): array
            {
                // `Doctrine\DBAL\Exception` est une interface marqueur (DBAL 4.x) : on lève une
                // exception concrète qui l'implémente, comme le ferait `TableNotFoundException`.
                throw new class ('relation "auth_role" does not exist') extends RuntimeException implements DbalException {};
            }

            public function findByName(TenantId $tenant, string $name): ?Role
            {
                return null;
            }

            public function save(Role $role): void
            {
            }
        };

        $voter = new PermissionVoter(new Authorizer($throwingRoles, new RecordingSecurityAuditLogger()));
        $token = $this->tokenFor($this->user(['Contrôle de gestion']));

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote($token, null, ['view:project_financials']),
        );
    }

    /**
     * @param list<string> $roleNames
     */
    private function user(array $roleNames): User
    {
        return new User($this->tenant, 'collaborateur@agence.test', 'hash', $roleNames);
    }

    private function tokenFor(User $user): TokenInterface
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }
}
