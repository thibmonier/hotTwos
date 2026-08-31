<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Tenant;

use App\Domain\Tenant\TenantId;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

/**
 * US-001 — identifiant de tenant (INV-1). Value object immuable adossé à un UUID.
 */
final class TenantIdTest extends TestCase
{
    public function testGeneratesAValidIdentifier(): void
    {
        $id = TenantId::generate();

        self::assertNotSame('', $id->toString());
    }

    public function testCanBeRebuiltFromItsStringForm(): void
    {
        $id = TenantId::generate();

        $rebuilt = TenantId::fromString($id->toString());

        self::assertTrue($id->equals($rebuilt));
    }

    public function testTwoGeneratedIdentifiersDiffer(): void
    {
        self::assertFalse(TenantId::generate()->equals(TenantId::generate()));
    }

    public function testRejectsAMalformedIdentifier(): void
    {
        $this->expectException(InvalidArgumentException::class);

        TenantId::fromString('not-a-uuid');
    }
}
