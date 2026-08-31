<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Observability;

use App\Infrastructure\Observability\MetricsCollector;
use PHPUnit\Framework\TestCase;

/**
 * US-008 — l'histogramme des temps de réponse est exposé au format Prometheus, avec des
 * tranches cumulatives permettant le calcul de la P95.
 */
final class MetricsCollectorTest extends TestCase
{
    public function testRendersPrometheusHistogram(): void
    {
        $collector = new MetricsCollector();
        $collector->record(0.03);  // ≤ 0.05
        $collector->record(0.2);   // ≤ 0.25
        $collector->record(3.0);   // ≤ 5.0

        $output = $collector->render();

        self::assertStringContainsString('http_requests_total 3', $output);
        self::assertStringContainsString('# TYPE http_request_duration_seconds histogram', $output);
        // Cumulatif : 1 requête ≤ 0.05, 2 ≤ 0.25, 3 ≤ 5 et +Inf.
        self::assertStringContainsString('http_request_duration_seconds_bucket{le="0.05"} 1', $output);
        self::assertStringContainsString('http_request_duration_seconds_bucket{le="0.25"} 2', $output);
        self::assertStringContainsString('http_request_duration_seconds_bucket{le="5"} 3', $output);
        self::assertStringContainsString('http_request_duration_seconds_bucket{le="+Inf"} 3', $output);
        self::assertStringContainsString('http_request_duration_seconds_count 3', $output);
    }

    public function testEmptyCollectorRendersZeroes(): void
    {
        $output = new MetricsCollector()->render();

        self::assertStringContainsString('http_requests_total 0', $output);
        self::assertStringContainsString('http_request_duration_seconds_count 0', $output);
    }
}
