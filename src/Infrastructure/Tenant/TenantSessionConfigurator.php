<?php

declare(strict_types=1);

namespace App\Infrastructure\Tenant;

use App\Application\Tenant\TenantContext;
use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * US-001 / TECH-2 — positionne le tenant courant sur la session PostgreSQL, activant la
 * seconde barrière d'isolation (RLS, ARC-34) au runtime.
 *
 * S'exécute après {@see AuthenticatedTenantResolver} (qui résout le tenant, priorité 6).
 * Émet, pour chaque requête principale, `SET app.current_tenant = '<uuid>'` quand un tenant
 * est positionné, sinon `RESET app.current_tenant` — ce **reset systématique** garantit
 * qu'aucun contexte ne fuit d'une requête à l'autre sur un worker FrankenPHP (RSQ-15).
 *
 * En production, l'application se connecte via le rôle `hotones_app` (non-superutilisateur) :
 * les politiques RLS s'appliquent réellement. En dev/CI (superutilisateur), le réglage est
 * inerte — l'isolation reste alors portée par le filtre ORM (ARC-33).
 */
#[AsEventListener(event: RequestEvent::class, priority: 4)]
final readonly class TenantSessionConfigurator
{
    public function __construct(
        private Connection $connection,
        private TenantContext $tenantContext,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->tenantContext->hasTenant()) {
            $this->connection->executeStatement('RESET app.current_tenant');

            return;
        }

        // Le tenant est un UUID validé ({@see \App\Domain\Tenant\TenantId}) — interpolation sûre
        // (SET n'accepte pas de paramètre lié).
        $this->connection->executeStatement(sprintf(
            "SET app.current_tenant = '%s'",
            $this->tenantContext->current()->toString(),
        ));
    }
}
