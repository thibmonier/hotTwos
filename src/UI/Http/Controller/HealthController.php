<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Health\HealthCheck;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Adaptateur HTTP mince : traduit la requête en appel de cas d'usage et sérialise la réponse.
 * Aucune logique métier ici (ARC-15). Le cas d'usage {@see HealthCheck} porte le comportement.
 */
final readonly class HealthController
{
    public function __construct(
        private HealthCheck $healthCheck,
    ) {
    }

    #[Route('/health', name: 'health', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse($this->healthCheck->status());
    }
}
