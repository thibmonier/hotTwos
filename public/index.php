<?php

declare(strict_types=1);

use App\Kernel;
use Symfony\Component\DependencyInjection\ServicesResetter;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

require_once dirname(__DIR__) . '/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');

/** @var array{APP_ENV: string, APP_DEBUG?: string|bool} $env */
$env = $_SERVER;
$kernel = new Kernel((string) $env['APP_ENV'], (bool) ($env['APP_DEBUG'] ?? false));

$handler = static function () use ($kernel): void {
    $request = Request::createFromGlobals();
    $response = $kernel->handle($request);
    $response->send();
    $kernel->terminate($request, $response);
};

// Hors mode worker (php_server par requête, serveur intégré, CLI) : une requête puis fin.
// La fonction frankenphp_handle_request n'est définie que dans un worker FrankenPHP.
if (!function_exists('frankenphp_handle_request')) {
    $handler();

    return;
}

// Mode worker FrankenPHP (ADR-2) : le kernel est chargé une fois et réutilisé entre
// requêtes. Entre deux requêtes, on réinitialise les services taggés kernel.reset —
// dont RequestTenantContext — pour ne laisser fuir aucun état (RSQ-15, ARC-47).
$kernel->boot();
$resetter = $kernel->getContainer()->get('app.services_resetter');
$maxRequests = (int) ($env['MAX_REQUESTS'] ?? 0);

for ($handledRequests = 0; 0 === $maxRequests || $handledRequests < $maxRequests; ++$handledRequests) {
    $keepRunning = frankenphp_handle_request($handler);

    if ($resetter instanceof ServicesResetter) {
        $resetter->reset();
    }
    gc_collect_cycles();

    if (!$keepRunning) {
        break;
    }
}
