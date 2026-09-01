<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\EventListener;

use App\Domain\Pricing\PricingException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

/**
 * Traduit une erreur métier de tarification (chevauchement, valeur ≤ 0, profil invalide,
 * confirmation rétroactive requise) en 422, sans exposer de trace (US-011).
 */
#[AsEventListener(event: ExceptionEvent::class)]
final class PricingExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();
        if (!$throwable instanceof PricingException) {
            return;
        }

        $event->setResponse(new JsonResponse(
            ['error' => $throwable->getMessage()],
            JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
        ));
    }
}
