<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Tenant;

use App\Application\Tenant\TenantContext;
use App\Domain\Tenant\TenantId;
use App\Infrastructure\Tenant\TenantSessionConfigurator;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * US-001 / TECH-2 — le tenant courant est propagé à la session PostgreSQL (RLS runtime),
 * et systématiquement réinitialisé pour éviter toute fuite inter-requêtes (RSQ-15).
 */
final class TenantSessionConfiguratorTest extends TestCase
{
    public function testSetsCurrentTenantWhenPresent(): void
    {
        $tenant = TenantId::generate();

        $context = $this->createStub(TenantContext::class);
        $context->method('hasTenant')->willReturn(true);
        $context->method('current')->willReturn($tenant);

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('executeStatement')
            ->with(self::stringContains($tenant->toString()));

        (new TenantSessionConfigurator($connection, $context))($this->event(HttpKernelInterface::MAIN_REQUEST));
    }

    public function testResetsWhenNoTenant(): void
    {
        $context = $this->createStub(TenantContext::class);
        $context->method('hasTenant')->willReturn(false);

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('executeStatement')
            ->with('RESET app.current_tenant');

        (new TenantSessionConfigurator($connection, $context))($this->event(HttpKernelInterface::MAIN_REQUEST));
    }

    public function testIgnoresSubRequests(): void
    {
        $context = $this->createStub(TenantContext::class);
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::never())->method('executeStatement');

        (new TenantSessionConfigurator($connection, $context))($this->event(HttpKernelInterface::SUB_REQUEST));
    }

    private function event(int $type): RequestEvent
    {
        return new RequestEvent($this->createStub(HttpKernelInterface::class), new Request(), $type);
    }
}
