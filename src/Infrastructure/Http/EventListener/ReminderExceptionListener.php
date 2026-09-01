<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\EventListener;

use App\Domain\Reminder\ReminderException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

/**
 * Traduit une erreur métier de relance (paramètres hors bornes) en 422, sans exposer de trace (US-056).
 */
#[AsEventListener(event: ExceptionEvent::class)]
final class ReminderExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();
        if (!$throwable instanceof ReminderException) {
            return;
        }

        $event->setResponse(new JsonResponse(['error' => $throwable->getMessage()], JsonResponse::HTTP_UNPROCESSABLE_ENTITY));
    }
}
