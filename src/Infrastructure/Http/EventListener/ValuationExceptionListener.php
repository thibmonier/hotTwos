<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\EventListener;

use App\Domain\Valuation\PeriodClosedException;
use App\Domain\Valuation\ValuationException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

/**
 * Traduit les erreurs métier de valorisation (US-060) en réponses HTTP, sans exposer de trace :
 * une période clôturée → **423 Locked** (CA-5), toute autre erreur de valorisation → 422.
 */
#[AsEventListener(event: ExceptionEvent::class)]
final class ValuationExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();
        if (!$throwable instanceof ValuationException) {
            return;
        }

        $status = $throwable instanceof PeriodClosedException
            ? JsonResponse::HTTP_LOCKED
            : JsonResponse::HTTP_UNPROCESSABLE_ENTITY;

        $event->setResponse(new JsonResponse(['error' => $throwable->getMessage()], $status));
    }
}
