<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\EventListener;

use App\Domain\Period\PeriodException;
use App\Domain\Period\PeriodLockedException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

/**
 * Traduit les erreurs métier de gestion des périodes (US-057) en réponses HTTP, sans trace :
 * une imputation en période clôturée → **423 Locked** (CA-4), toute autre erreur → 422.
 */
#[AsEventListener(event: ExceptionEvent::class)]
final class PeriodExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();
        if (!$throwable instanceof PeriodException) {
            return;
        }

        $status = $throwable instanceof PeriodLockedException
            ? JsonResponse::HTTP_LOCKED
            : JsonResponse::HTTP_UNPROCESSABLE_ENTITY;

        $event->setResponse(new JsonResponse(['error' => $throwable->getMessage()], $status));
    }
}
