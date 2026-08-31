<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Tenant;

use App\Domain\Tenant\TenantId;
use App\Infrastructure\Tenant\RequestTenantContext;
use PHPUnit\Framework\TestCase;
use LogicException;

/**
 * US-001 — le contexte de tenant est positionné puis effacé par requête (ARC-61),
 * garantie contre la fuite d'état en mode worker (ARC-47, RSQ-15).
 */
final class RequestTenantContextTest extends TestCase
{
    public function testExposesTheTenantAfterSwitch(): void
    {
        $context = new RequestTenantContext();
        $tenant = TenantId::generate();

        $context->switchTo($tenant);

        self::assertTrue($context->hasTenant());
        self::assertTrue($context->current()->equals($tenant));
    }

    public function testClearRemovesAnyPreviousTenant(): void
    {
        $context = new RequestTenantContext();
        $context->switchTo(TenantId::generate());

        $context->clear();

        self::assertFalse($context->hasTenant());
    }

    public function testReadingWithoutTenantFails(): void
    {
        $context = new RequestTenantContext();

        $this->expectException(LogicException::class);
        $context->current();
    }
}
