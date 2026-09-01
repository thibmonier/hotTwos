<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Completeness;

use App\Application\Authorization\Authorizer;
use App\Application\Completeness\CompletenessScope;
use App\Domain\Authorization\AccessDeniedException;
use App\Domain\Authorization\DataScope;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\Role;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Tests\Support\Authorization\InMemoryRoleRepository;
use App\Tests\Support\Authorization\RecordingSecurityAuditLogger;
use App\Tests\Support\User\InMemoryUserRepository;
use PHPUnit\Framework\TestCase;

/**
 * US-058 (T-058-02, CA-5) — un collaborateur ne voit que lui-même ; le périmètre « équipe » exige
 * `VIEW_TEAM_COMPLETENESS` (403 sinon).
 */
final class CompletenessScopeTest extends TestCase
{
    private TenantId $tenant;
    private CompletenessScope $scope;
    private User $marc;
    private User $camille;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $roles = new InMemoryRoleRepository();
        $roles->add(new Role($this->tenant, 'Chef de projet', [Permission::VIEW_TEAM_COMPLETENESS], DataScope::OWN_PROJECTS));
        $roles->add(new Role($this->tenant, 'Collaborateur', [Permission::VIEW_PROJECT], DataScope::OWN));

        $users = new InMemoryUserRepository();
        $this->marc = new User($this->tenant, 'marc@agence.test', 'hash', ['Chef de projet']);
        $this->camille = new User($this->tenant, 'camille@agence.test', 'hash', ['Collaborateur']);
        $users->register($this->tenant, $this->marc->id());
        $users->register($this->tenant, $this->camille->id());

        $this->scope = new CompletenessScope(new Authorizer($roles, new RecordingSecurityAuditLogger()), $users);
    }

    public function testCollaboratorSeesOnlySelf(): void
    {
        self::assertSame([$this->camille->id()], $this->scope->resolve($this->camille, false));
    }

    public function testCollaboratorCannotRequestTeamScope(): void
    {
        $this->expectException(AccessDeniedException::class);

        $this->scope->resolve($this->camille, true);
    }

    public function testManagerSeesTheWholeTenant(): void
    {
        $ids = $this->scope->resolve($this->marc, true);

        self::assertContains($this->marc->id(), $ids);
        self::assertContains($this->camille->id(), $ids);
        self::assertCount(2, $ids);
    }
}
