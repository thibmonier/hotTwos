<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\EventListener;

use App\Domain\Project\ProjectException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

/**
 * Traduit une erreur métier projet (RG-PRJ-1, transition de statut invalide, projet clôturé…) en 422,
 * sans exposer de trace (US-030+).
 */
#[AsEventListener(event: ExceptionEvent::class)]
final class ProjectExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();
        if (!$throwable instanceof ProjectException) {
            return;
        }

        $event->setResponse(new JsonResponse(['error' => $throwable->getMessage()], JsonResponse::HTTP_UNPROCESSABLE_ENTITY));
    }
}
