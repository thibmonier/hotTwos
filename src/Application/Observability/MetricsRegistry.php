<?php

declare(strict_types=1);

namespace App\Application\Observability;

/**
 * Port d'enregistrement et d'exposition des métriques applicatives (US-008, ADR-14).
 * L'implémentation (format Prometheus) vit en infrastructure ; l'UI et les listeners ne
 * connaissent que ce contrat.
 */
interface MetricsRegistry
{
    public function record(float $durationSeconds): void;

    /**
     * @return string exposition au format texte Prometheus
     */
    public function render(): string;
}
