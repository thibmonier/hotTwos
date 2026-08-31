<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Tenant;

use App\Application\Tenant\TenantContext;
use App\Infrastructure\Tenant\TenantFilterConfigurator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * US-001 — gardes de l'activation de l'isolation (ARC-61) : le filtre n'est jamais
 * activé sans tenant positionné, ni sur une sous-requête.
 *
 * Le chemin d'activation effectif (enable + setParameter) est couvert par le test
 * d'intégration d'isolation (ENF-SEC-4) — SQLFilter::setParameter() étant final,
 * il n'est pas mockable en test unitaire.
 */
final class TenantFilterConfiguratorTest extends TestCase
{
    public function testDoesNothingWithoutTenant(): void
    {
        $context = $this->createMock(TenantContext::class);
        $context->method('hasTenant')->willReturn(false);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('getFilters');

        new TenantFilterConfigurator($entityManager, $context)
            ->onKernelRequest($this->event(HttpKernelInterface::MAIN_REQUEST));
    }

    public function testDoesNothingOnSubRequest(): void
    {
        $context = $this->createMock(TenantContext::class);
        $context->expects(self::never())->method('hasTenant');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('getFilters');

        new TenantFilterConfigurator($entityManager, $context)
            ->onKernelRequest($this->event(HttpKernelInterface::SUB_REQUEST));
    }

    private function event(int $requestType): RequestEvent
    {
        return new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            $requestType,
        );
    }
}
