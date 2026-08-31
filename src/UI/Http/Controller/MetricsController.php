<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Observability\MetricsRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * US-008 (ADR-14) — exposition des métriques au format Prometheus. Scrapable par un
 * Prometheus/Grafana pour le suivi de la P95 des temps de réponse.
 */
final class MetricsController extends AbstractController
{
    public function __construct(private readonly MetricsRegistry $metrics)
    {
    }

    #[Route('/metrics', name: 'metrics', methods: ['GET'])]
    public function __invoke(): Response
    {
        return new Response(
            $this->metrics->render(),
            Response::HTTP_OK,
            ['Content-Type' => 'text/plain; version=0.0.4; charset=utf-8'],
        );
    }
}
