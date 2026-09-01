<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\EventListener;

use App\Domain\Organization\OrganizationException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

/**
 * Traduit une erreur métier de l'organisation (cycle, chevauchement, unité invalide) en 422,
 * sans exposer de trace (US-010). Miroir du listener d'habilitation (403).
 */
#[AsEventListener(event: ExceptionEvent::class)]
final class OrganizationExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();
        if (!$throwable instanceof OrganizationException) {
            return;
        }

        $event->setResponse(new JsonResponse(
            ['error' => $throwable->getMessage()],
            JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
        ));
    }
}
