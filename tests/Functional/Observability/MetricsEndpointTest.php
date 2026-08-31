<?php

declare(strict_types=1);

namespace App\Tests\Functional\Observability;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * US-008 — l'endpoint /metrics répond au format Prometheus et n'exige pas d'authentification
 * (scrape). Il reflète le trafic déjà servi.
 */
final class MetricsEndpointTest extends WebTestCase
{
    public function testMetricsEndpointExposesPrometheusFormat(): void
    {
        $client = self::createClient();

        // Une requête servie, puis lecture des métriques.
        $client->request('GET', '/health');
        $client->request('GET', '/metrics');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'text/plain; version=0.0.4; charset=utf-8');
        $body = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('# TYPE http_requests_total counter', $body);
        self::assertStringContainsString('http_request_duration_seconds_bucket', $body);
    }
}
