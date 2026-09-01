<?php

declare(strict_types=1);

/*
 * Smoke de déploiement (US-055/TECH-3, action rétro Sprint 2). Vérifie que les endpoints
 * critiques répondent comme attendu après un déploiement. Sortie non nulle si une
 * assertion échoue — utilisable en étape de CD ou à la main : `php scripts/smoke.php <URL>`.
 */

$base = rtrim($argv[1] ?? getenv('SMOKE_URL') ?: '', '/');
if ('' === $base) {
    fwrite(\STDERR, "Usage : php scripts/smoke.php https://mon-app.example\n");
    exit(2);
}

/** @var list<array{method: string, path: string, expected: int, body?: string}> $checks */
$checks = [
    ['method' => 'GET', 'path' => '/health', 'expected' => 200],
    ['method' => 'GET', 'path' => '/api/status', 'expected' => 200],
    ['method' => 'GET', 'path' => '/metrics', 'expected' => 200],
    // Non authentifié : la ressource protégée renvoie 401, le login rejette (401).
    ['method' => 'GET', 'path' => '/api/me', 'expected' => 401],
    ['method' => 'POST', 'path' => '/api/login', 'expected' => 401, 'body' => '{"email":"smoke@invalid.test","password":"nope"}'],
];

$failures = 0;
foreach ($checks as $check) {
    $status = request($base . $check['path'], $check['method'], $check['body'] ?? null);
    $ok = $status === $check['expected'];
    printf("%s %-6s %-14s → %s (attendu %d)\n", $ok ? '✅' : '❌', $check['method'], $check['path'], $status, $check['expected']);
    if (!$ok) {
        ++$failures;
    }
}

if ($failures > 0) {
    fwrite(\STDERR, sprintf("\nSmoke ÉCHOUÉ : %d assertion(s) en erreur.\n", $failures));
    exit(1);
}

echo "\nSmoke OK.\n";
exit(0);

function request(string $url, string $method, ?string $body): int|string
{
    $context = stream_context_create([
        'http' => [
            'method' => $method,
            'header' => "Content-Type: application/json\r\n",
            'content' => $body ?? '',
            'ignore_errors' => true,
            'timeout' => 15,
        ],
    ]);

    $headers = @get_headers($url, true, $context);
    if (false === $headers || !isset($headers[0])) {
        return 'INJOIGNABLE';
    }

    // La ligne de statut est du type "HTTP/1.1 200 OK".
    if (preg_match('#\s(\d{3})\s#', (string) $headers[0], $matches) === 1) {
        return (int) $matches[1];
    }

    return 'INCONNU';
}
