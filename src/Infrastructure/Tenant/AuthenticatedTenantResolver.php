<?php

declare(strict_types=1);

namespace App\Infrastructure\Tenant;

use App\Application\Tenant\TenantSwitcher;
use App\Domain\User\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Résout le tenant courant depuis l'utilisateur authentifié (ENF-SEC-4, HAB-4) :
 * une fois connecté, le tenant provient de l'utilisateur — jamais d'un en-tête ou
 * paramètre forgeable par le client. S'exécute après le pare-feu de sécurité (priorité < 8)
 * et avant l'activation du filtre d'isolation ({@see TenantFilterConfigurator}).
 */
#[AsEventListener(event: RequestEvent::class, priority: 6)]
final readonly class AuthenticatedTenantResolver
{
    public function __construct(
        private Security $security,
        private TenantSwitcher $tenantSwitcher,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $user = $this->security->getUser();
        if ($user instanceof User) {
            $this->tenantSwitcher->switchTo($user->tenantId());
        }
    }
}
