<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging;

use App\Application\Messaging\TenantAwareMessage;
use App\Application\Tenant\TenantSwitcher;
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
 */
final readonly class TenantContextMiddleware implements MiddlewareInterface
{
    public function __construct(
        private TenantSwitcher $tenantSwitcher,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();
        $isConsuming = $envelope->last(ReceivedStamp::class) instanceof ReceivedStamp;

        if (!$isConsuming || !$message instanceof TenantAwareMessage) {
            return $stack->next()->handle($envelope, $stack);
        }

        $this->tenantSwitcher->switchTo($message->tenantId());

        try {
            return $stack->next()->handle($envelope, $stack);
        } finally {
            $this->tenantSwitcher->clear();
        }
    }
}
