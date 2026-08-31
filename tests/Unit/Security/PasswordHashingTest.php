<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\SodiumPasswordHasher;

/**
 * US-002 / ENF-SEC-3 — le hachage retenu est Argon2id (sodium), jamais bcrypt/MD5/SHA1.
 * Le service configuré (security.yaml : algorithm: sodium) s'appuie sur ce hasher.
 */
final class PasswordHashingTest extends TestCase
{
    public function testProducesArgon2idHashesAndVerifiesThem(): void
    {
        $hasher = new SodiumPasswordHasher();

        $hash = $hasher->hash('un-mot-de-passe-solide');

        self::assertStringStartsWith('$argon2id$', $hash);
        self::assertTrue($hasher->verify($hash, 'un-mot-de-passe-solide'));
        self::assertFalse($hasher->verify($hash, 'mauvais-mot-de-passe'));
    }
}
