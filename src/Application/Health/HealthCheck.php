<?php

declare(strict_types=1);

namespace App\Application\Health;

/**
 * Cas d'usage applicatif : renvoie l'état de santé de l'application.
 *
 * Volontairement indépendant du transport (HTTP/CLI) — ADR-1 (cœur API-first),
 * ARC-17 (tout cas d'usage invocable sans HTTP). Consommé par un adaptateur.
 */
final readonly class HealthCheck
{
    public function __construct(
        private string $appName = 'HotOnes',
    ) {
    }

    /**
     * @return array{status: string, app: string}
     */
    public function status(): array
    {
        return [
            'status' => 'ok',
            'app' => $this->appName,
        ];
    }
}
