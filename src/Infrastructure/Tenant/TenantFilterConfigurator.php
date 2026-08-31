<?php

declare(strict_types=1);

namespace App\Infrastructure\Tenant;

use App\Application\Tenant\TenantContext;
use App\Infrastructure\Persistence\Doctrine\Filter\TenantFilter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Active le filtre d'isolation Doctrine ({@see TenantFilter}) au début de chaque requête
 * principale, avec le tenant courant (ARC-61). Sans tenant positionné, le filtre reste
 * désactivé (ex. contexte d'administration éditeur, tracé à part — ENF-SEC-8).
 */
final readonly class TenantFilterConfigurator
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TenantContext $tenantContext,
    ) {
    }

    #[AsEventListener(event: RequestEvent::class)]
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || !$this->tenantContext->hasTenant()) {
            return;
        }

        $filter = $this->entityManager->getFilters()->enable(TenantFilter::NAME);
        $filter->setParameter(TenantFilter::PARAMETER, $this->tenantContext->current()->toString());
    }
}
