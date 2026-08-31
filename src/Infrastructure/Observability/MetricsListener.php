<?php

declare(strict_types=1);

namespace App\Infrastructure\Observability;

use App\Application\Observability\MetricsRegistry;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

/**
 * Mesure le temps de réponse de chaque requête principale et l'enregistre dans le
 * {@see MetricsRegistry} (US-008, P95). La route /metrics elle-même est ignorée pour
 * ne pas biaiser les mesures.
 */
final class MetricsListener
{
    private ?float $startedAt = null;

    public function __construct(private readonly MetricsRegistry $collector)
    {
    }

    #[AsEventListener(event: RequestEvent::class, priority: 4096)]
    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->startedAt = microtime(true);
    }

    #[AsEventListener(event: TerminateEvent::class)]
    public function onTerminate(TerminateEvent $event): void
    {
        if (null === $this->startedAt || '/metrics' === $event->getRequest()->getPathInfo()) {
            $this->startedAt = null;

            return;
        }

        $this->collector->record(microtime(true) - $this->startedAt);
        $this->startedAt = null;
    }
}
