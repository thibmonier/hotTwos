<?php

declare(strict_types=1);

namespace App\Application\Analytics;

use App\Application\Analytics\Message\AnalyticsRebuildRequested;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * US-060 (T-060-06) — rematérialise `fact_project_revenue` à réception d'{@see AnalyticsRebuildRequested}.
 *
 * Réutilise {@see RebuildAnalytics} (purge + rejeu transactionnel des événements, dédup « dernière
 * reconnaissance gagne ») plutôt qu'une projection incrémentale : une seule source de vérité, pas de
 * double comptage à la re-valorisation (CA-4). La fact table reste écrite uniquement par le projecteur (ADR-9).
 */
#[AsMessageHandler]
final readonly class ProjectAnalyticsHandler
{
    public function __construct(private RebuildAnalytics $rebuild)
    {
    }

    public function __invoke(AnalyticsRebuildRequested $message): void
    {
        $this->rebuild->forTenant($message->tenantId());
    }
}
