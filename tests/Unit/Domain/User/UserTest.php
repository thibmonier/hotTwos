<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User;

use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * US-002 — l'utilisateur est porté par un tenant, s'authentifie par e-mail et
 * possède toujours au moins ROLE_USER.
 */
final class UserTest extends TestCase
{
    public function testIsBoundToItsTenantAndIdentifiedByEmail(): void
    {
        $tenant = TenantId::generate();
        $user = new User($tenant, 'camille@agence.test', 'hashed');

        self::assertInstanceOf(UserInterface::class, $user);
        self::assertInstanceOf(PasswordAuthenticatedUserInterface::class, $user);
        self::assertTrue($user->tenantId()->equals($tenant));
        self::assertSame('camille@agence.test', $user->getUserIdentifier());
        self::assertSame('hashed', $user->getPassword());
    }

    public function testAlwaysHasRoleUser(): void
    {
        $user = new User(TenantId::generate(), 'nadia@agence.test', 'hashed', ['ROLE_ADMIN']);

        self::assertContains('ROLE_USER', $user->getRoles());
        self::assertContains('ROLE_ADMIN', $user->getRoles());
    }

    public function testRolesAreDeduplicated(): void
    {
        $user = new User(TenantId::generate(), 'marc@agence.test', 'hashed', ['ROLE_USER', 'ROLE_USER']);

        self::assertSame(['ROLE_USER'], $user->getRoles());
    }
}
