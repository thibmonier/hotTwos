<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\EventListener;

use App\Domain\Authorization\AccessDeniedException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

/**
 * Traduit un refus d'habilitation métier ({@see AccessDeniedException}) en 403 Forbidden
 * JSON (US-003). Le contrôle reste dans la couche applicative (ARC-19) ; ce listener
 * ne fait que présenter le motif au client sans divulguer d'internes (règle 11 — §7).
 */
#[AsEventListener(event: ExceptionEvent::class)]
final class AccessDeniedExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();
        if (!$throwable instanceof AccessDeniedException) {
            return;
        }

        $event->setResponse(new JsonResponse(
            ['error' => $throwable->getMessage()],
            JsonResponse::HTTP_FORBIDDEN,
        ));
    }
}
