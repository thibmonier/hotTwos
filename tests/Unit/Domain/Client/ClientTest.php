<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Client;

use App\Domain\Client\Client;
use App\Domain\Tenant\TenantId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * US-014 — compte client (tranche minimale) : invariants nom/SIREN, renommage.
 */
final class ClientTest extends TestCase
{
    public function testCreatesClientWithNameAndOptionalSiren(): void
    {
        $client = new Client(TenantId::generate(), 'ACME Tourisme', '123456789');

        self::assertSame('ACME Tourisme', $client->name());
        self::assertSame('123456789', $client->siren());
    }

    public function testSirenIsOptional(): void
    {
        self::assertNull(new Client(TenantId::generate(), 'Globex')->siren());
    }

    public function testRejectsEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Client(TenantId::generate(), '  ');
    }

    public function testRejectsInvalidSiren(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Client(TenantId::generate(), 'ACME', '1234');
    }

    public function testRenameUpdatesNameAndSiren(): void
    {
        $client = new Client(TenantId::generate(), 'ACME');
        $client->rename('ACME Tourisme', '987654321');

        self::assertSame('ACME Tourisme', $client->name());
        self::assertSame('987654321', $client->siren());
    }
}
