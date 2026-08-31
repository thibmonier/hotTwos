<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Authorization;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\AccessDeniedException;
use App\Domain\Authorization\DataScope;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\Role;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Tests\Support\Authorization\InMemoryRoleRepository;
use App\Tests\Support\Authorization\RecordingSecurityAuditLogger;
use PHPUnit\Framework\TestCase;

/**
 * US-003 — le contrôle d'habilitation (permission + périmètre) est fait côté serveur
 * et tracé (ARC-19, ARC-106, HAB-6). Couvre les mécanismes de CA-1, CA-2, CA-3, CA-5, CA-6.
 */
final class AuthorizerTest extends TestCase
{
    private TenantId $tenant;
    private InMemoryRoleRepository $roles;
    private RecordingSecurityAuditLogger $audit;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $this->roles = new InMemoryRoleRepository();
        $this->audit = new RecordingSecurityAuditLogger();

        $this->roles->add(new Role($this->tenant, 'Chef de projet', [
            Permission::VIEW_PROJECT,
            Permission::EDIT_PROJECT,
        ], DataScope::OWN_PROJECTS));

        $this->roles->add(new Role($this->tenant, 'Resource Manager', [
            Permission::VIEW_PROJECT,
            Permission::VIEW_COLLABORATOR_COST,
        ], DataScope::POOL));

        $this->roles->add(new Role($this->tenant, 'Administrateur', [
            Permission::MANAGE_ROLES,
        ], DataScope::TENANT));
    }

    public function testChefDeProjetCannotSeeCollaboratorCost(): void
    {
        $marc = $this->user('marc@agence.test', ['Chef de projet']);

        self::assertFalse($this->authorizer()->can($marc, Permission::VIEW_COLLABORATOR_COST));

        $this->expectException(AccessDeniedException::class);
        $this->authorizer()->authorizeSensitiveRead($marc, Permission::VIEW_COLLABORATOR_COST, 'collaborator:camille');
    }

    public function testUnauthorizedActionIsTraced(): void
    {
        $camille = $this->user('camille@agence.test', ['Collaborateur']); // rôle inconnu → aucune permission

        try {
            $this->authorizer()->ensureCan($camille, Permission::DELETE_PROJECT);
            self::fail('Une AccessDeniedException était attendue.');
        } catch (AccessDeniedException) {
        }

        self::assertTrue($this->audit->has('unauthorized_action_attempt'));
    }

    public function testResourceManagerSensitiveReadIsAllowedAndTraced(): void
    {
        $sophie = $this->user('sophie@agence.test', ['Resource Manager']);

        $this->authorizer()->authorizeSensitiveRead($sophie, Permission::VIEW_COLLABORATOR_COST, 'collaborator:camille');

        self::assertTrue($this->audit->has('sensitive_data_read'));
        $event = $this->audit->events[0];
        self::assertSame('sophie@agence.test', $event['actorId']);
        self::assertSame($this->tenant->toString(), $event['tenantId']);
        self::assertSame('collaborator:camille', $event['context']['resource']);
    }

    public function testOutOfScopeAccessIsRefusedAndTraced(): void
    {
        $marc = $this->user('marc@agence.test', ['Chef de projet']); // périmètre OWN_PROJECTS

        try {
            $this->authorizer()->ensureWithinScope($marc, DataScope::TENANT, 'project:x');
            self::fail('Une AccessDeniedException était attendue.');
        } catch (AccessDeniedException $exception) {
            self::assertSame('Ressource hors périmètre autorisé', $exception->getMessage());
        }

        self::assertTrue($this->audit->has('out_of_scope_access_attempt'));
    }

    public function testEffectiveScopeIsTheWidestAmongRoles(): void
    {
        $polyvalent = $this->user('polyvalent@agence.test', ['Chef de projet', 'Resource Manager']);

        self::assertSame(DataScope::POOL, $this->authorizer()->effectiveScope($polyvalent));
    }

    public function testGrantingRoleBeyondOwnScopeIsRefusedAndTraced(): void
    {
        $jordan = $this->user('jordan@agence.test', ['Chef de projet']); // périmètre OWN_PROJECTS
        $wideRole = new Role($this->tenant, 'Resource Manager', [Permission::VIEW_COLLABORATOR_COST], DataScope::POOL);

        try {
            $this->authorizer()->ensureCanGrant($jordan, $wideRole);
            self::fail('Une AccessDeniedException était attendue.');
        } catch (AccessDeniedException) {
        }

        self::assertTrue($this->audit->has('privilege_escalation_attempt'));
    }

    public function testGrantingRoleWithinOwnScopeIsAllowed(): void
    {
        $admin = $this->user('admin@agence.test', ['Administrateur']); // périmètre TENANT
        $narrowRole = new Role($this->tenant, 'Chef de projet', [Permission::VIEW_PROJECT], DataScope::OWN_PROJECTS);

        $this->authorizer()->ensureCanGrant($admin, $narrowRole);

        self::assertFalse($this->audit->has('privilege_escalation_attempt'));
    }

    /**
     * @param list<string> $roleNames
     */
    private function user(string $email, array $roleNames): User
    {
        return new User($this->tenant, $email, 'hash', $roleNames);
    }

    private function authorizer(): Authorizer
    {
        return new Authorizer($this->roles, $this->audit);
    }
}
