<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User;

use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * US-067 — le nom d'affichage d'un utilisateur est « Prénom Nom » lorsqu'il est renseigné, et retombe
 * sur l'e-mail sinon (rétrocompatibilité CA-3 : jamais « null null », jamais d'écran cassé).
 */
final class UserDisplayNameTest extends TestCase
{
    public function testDisplayNameFallsBackToEmailWhenUnnamed(): void
    {
        $user = $this->user();

        self::assertSame('camille@agence.test', $user->displayName());
    }

    public function testDisplayNameUsesFirstAndLastName(): void
    {
        $user = $this->user();
        $user->rename('Camille', 'Martin');

        self::assertSame('Camille Martin', $user->displayName());
    }

    public function testDisplayNameWithPartialNameIsStillReadable(): void
    {
        $user = $this->user();
        $user->rename('Camille', null);

        self::assertSame('Camille', $user->displayName());
    }

    public function testRenameTrimsAndFallsBackWhenBlank(): void
    {
        $user = $this->user();
        $user->rename('  ', '   ');

        self::assertSame('camille@agence.test', $user->displayName());
    }

    public function testRenameRejectsTooLongName(): void
    {
        $user = $this->user();

        $this->expectException(InvalidArgumentException::class);
        $user->rename(str_repeat('a', 101), 'Martin');
    }

    private function user(): User
    {
        return new User(TenantId::generate(), 'camille@agence.test', 'hash', ['Collaborateur']);
    }
}
