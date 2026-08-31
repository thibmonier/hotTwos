<?php

declare(strict_types=1);

namespace App\Tests\Integration\Tenant;

use App\Domain\Tenant\TenantId;
use App\Infrastructure\Tenant\RequestTenantContext;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ServicesResetter;

/**
 * US-006 / ARC-47 (RSQ-15) — en mode worker, le contexte de tenant porté par requête ne
 * doit jamais fuir d'une requête à la suivante : Symfony le réinitialise entre requêtes
 * via le mécanisme `kernel.reset`. Ce test simule la frontière de requête (services reset).
 */
final class WorkerStateResetTest extends KernelTestCase
{
    public function testTenantContextIsClearedBetweenRequests(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $context = $container->get(RequestTenantContext::class);
        self::assertInstanceOf(RequestTenantContext::class, $context);

        // Requête N : un tenant est positionné.
        $context->switchTo(TenantId::generate());
        self::assertTrue($context->hasTenant());

        // Frontière de requête worker : Symfony réinitialise les services taggés kernel.reset.
        $resetter = $container->get('services_resetter');
        self::assertInstanceOf(ServicesResetter::class, $resetter);
        $resetter->reset();

        // Requête N+1 : aucun état résiduel du tenant précédent.
        self::assertFalse($context->hasTenant(), 'Le contexte de tenant doit être réinitialisé entre deux requêtes worker.');
    }
}
