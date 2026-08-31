<?php

declare(strict_types=1);

namespace App\Infrastructure\Observability;

use App\Application\Observability\MetricsRegistry;

/**
 * Collecteur de métriques au format Prometheus (US-008, ADR-14).
 *
 * Accumule le nombre de requêtes et un histogramme des temps de réponse. En mode worker
 * FrankenPHP, ce service (singleton, **non** taggé kernel.reset) persiste entre requêtes
 * et agrège donc les mesures du worker ; l'histogramme permet de calculer la P95 côté
 * Prometheus (`histogram_quantile(0.95, rate(http_request_duration_seconds_bucket[5m]))`).
 */
final class MetricsCollector implements MetricsRegistry
{
    /** Bornes supérieures des tranches de latence (secondes). */
    private const array BUCKETS = [0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0];

    private int $totalRequests = 0;
    private float $durationSum = 0.0;

    /** @var array<string, int> le (borne) => nombre cumulé */
    private array $bucketCounts = [];

    public function record(float $durationSeconds): void
    {
        ++$this->totalRequests;
        $this->durationSum += $durationSeconds;

        foreach (self::BUCKETS as $bucket) {
            if ($durationSeconds <= $bucket) {
                $key = $this->formatBucket($bucket);
                $this->bucketCounts[$key] = ($this->bucketCounts[$key] ?? 0) + 1;
            }
        }
    }

    public function render(): string
    {
        $lines = [
            '# HELP http_requests_total Nombre total de requêtes HTTP traitées.',
            '# TYPE http_requests_total counter',
            sprintf('http_requests_total %d', $this->totalRequests),
            '# HELP http_request_duration_seconds Histogramme des temps de réponse HTTP.',
            '# TYPE http_request_duration_seconds histogram',
        ];

        foreach (self::BUCKETS as $bucket) {
            $key = $this->formatBucket($bucket);
            // Les compteurs sont déjà cumulatifs (chaque requête incrémente toutes les
            // tranches dont la borne ≥ sa latence) — sémantique d'histogramme Prometheus.
            $lines[] = sprintf('http_request_duration_seconds_bucket{le="%s"} %d', $key, $this->bucketCounts[$key] ?? 0);
        }
        $lines[] = sprintf('http_request_duration_seconds_bucket{le="+Inf"} %d', $this->totalRequests);
        $lines[] = sprintf('http_request_duration_seconds_sum %s', $this->formatFloat($this->durationSum));
        $lines[] = sprintf('http_request_duration_seconds_count %d', $this->totalRequests);

        return implode("\n", $lines)."\n";
    }

    private function formatBucket(float $bucket): string
    {
        return rtrim(rtrim(sprintf('%.2f', $bucket), '0'), '.');
    }

    private function formatFloat(float $value): string
    {
        return rtrim(rtrim(sprintf('%.6f', $value), '0'), '.') ?: '0';
    }
}
