<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging;

use App\Application\Messaging\TenantAwareMessage;
use App\Application\Tenant\TenantSwitcher;
use App\Domain\Tenant\TenantId;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

/**
 * Pose le contexte de tenant à la **consommation** d'un message asynchrone, puis l'efface
 * systématiquement (ARC-47, RSQ-15).
 *
 * Ne s'active qu'à la réception depuis un transport ({@see ReceivedStamp}) : lors du *dispatch*
 * initial (typiquement dans une requête HTTP déjà scopée par l'utilisateur authentifié), le
 * contexte courant ne doit pas être modifié ni effacé. Le `finally` garantit qu'aucun tenant
 * ne fuit d'un message au suivant sur un worker à longue durée de vie.
 *
 * Deux barrières sont armées, en parité avec le chemin HTTP : le contexte **applicatif**
 * (`TenantSwitcher`, pour le filtre ORM) **et** la variable de session **PostgreSQL**
 * (`app.current_tenant`, pivot de la RLS). Hors requête HTTP, aucun `RequestEvent` ne déclenche
 * {@see \App\Infrastructure\Tenant\TenantSessionConfigurator} : sans ce `SET`, l'écriture d'un
 * handler (ex. valorisation US-060) s'exécuterait sans contexte RLS — rejetée sous rôle
 * non-superutilisateur, ou vulnérable à une écriture cross-tenant sur une connexion résiduelle.
 */
final readonly class TenantContextMiddleware implements MiddlewareInterface
{
    public function __construct(
        private TenantSwitcher $tenantSwitcher,
        private Connection $connection,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();
        $isConsuming = $envelope->last(ReceivedStamp::class) instanceof ReceivedStamp;

        if (!$isConsuming || !$message instanceof TenantAwareMessage) {
            return $stack->next()->handle($envelope, $stack);
        }

        $tenant = $message->tenantId();
        $this->tenantSwitcher->switchTo($tenant);
        $this->bindDatabaseSession($tenant);

        try {
            return $stack->next()->handle($envelope, $stack);
        } finally {
            $this->tenantSwitcher->clear();
            // RESET systématique : aucune variable de session ne fuit vers le message suivant
            // sur un worker à longue durée de vie (RSQ-15).
            $this->connection->executeStatement('RESET app.current_tenant');
        }
    }

    private function bindDatabaseSession(TenantId $tenant): void
    {
        // Le tenant est un UUID validé ({@see TenantId}) — interpolation sûre (SET n'accepte pas
        // de paramètre lié). Même barrière RLS que le listener HTTP (TenantSessionConfigurator).
        $this->connection->executeStatement(sprintf("SET app.current_tenant = '%s'", $tenant->toString()));
    }
}
