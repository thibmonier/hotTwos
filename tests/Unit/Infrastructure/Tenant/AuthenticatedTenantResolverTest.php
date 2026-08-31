<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Tenant;

use App\Application\Tenant\TenantSwitcher;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Infrastructure\Tenant\AuthenticatedTenantResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * US-002 — le tenant courant est résolu depuis l'utilisateur authentifié (ENF-SEC-4).
 */
final class AuthenticatedTenantResolverTest extends TestCase
{
    public function testPositionsTenantFromAuthenticatedUser(): void
    {
        $tenant = TenantId::generate();
        $user = new User($tenant, 'camille@agence.test', 'hash');

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user);

        $switcher = $this->createMock(TenantSwitcher::class);
        $switcher->expects(self::once())
            ->method('switchTo')
            ->with(self::callback(static fn (TenantId $id): bool => $id->equals($tenant)));

        (new AuthenticatedTenantResolver($security, $switcher))($this->event(HttpKernelInterface::MAIN_REQUEST));
    }

    public function testDoesNothingWhenNotAuthenticated(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $switcher = $this->createMock(TenantSwitcher::class);
        $switcher->expects(self::never())->method('switchTo');

        (new AuthenticatedTenantResolver($security, $switcher))($this->event(HttpKernelInterface::MAIN_REQUEST));
    }

    public function testIgnoresSubRequests(): void
    {
        $security = $this->createMock(Security::class);
        $security->expects(self::never())->method('getUser');

        $switcher = $this->createStub(TenantSwitcher::class);

        (new AuthenticatedTenantResolver($security, $switcher))($this->event(HttpKernelInterface::SUB_REQUEST));
    }

    private function event(int $type): RequestEvent
    {
        return new RequestEvent($this->createStub(HttpKernelInterface::class), new Request(), $type);
    }
}
