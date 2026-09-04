<?php

declare(strict_types=1);

namespace App\Application\Analytics;

use App\Application\Analytics\Message\AnalyticsRebuildRequested;
use App\Domain\Tenant\TenantId;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * US-060 (T-060-09) — coalescence des demandes de rematérialisation analytique.
 *
 * Le rebuild est un rejeu complet et idempotent : dispatcher un message par lot de validation
 * entraîne des rebuilds redondants sous rafale. Ce planificateur pose un drapeau par tenant : tant
 * qu'un rebuild est en attente, les demandes suivantes sont **coalescées** (aucun nouveau message).
 * Le handler {@see ProjectAnalyticsHandler} lève le drapeau **au début** du rebuild, de sorte qu'une
 * demande arrivée pendant le traitement ré-arme le drapeau et garantit un rebuild couvrant ses données
 * (cohérence éventuelle). Le drapeau expire (garde-fou) si le worker n'acquitte jamais.
 */
final readonly class AnalyticsRebuildScheduler
{
    private const int PENDING_TTL_SECONDS = 300;

    public function __construct(
        private MessageBusInterface $bus,
        private CacheItemPoolInterface $cache,
    ) {
    }

    public function schedule(TenantId $tenant): void
    {
        $item = $this->cache->getItem($this->key($tenant));
        if ($item->isHit()) {
            return; // Un rebuild est déjà en attente pour ce tenant : coalescé.
        }

        $item->set(true)->expiresAfter(self::PENDING_TTL_SECONDS);
        $this->cache->save($item);

        $this->bus->dispatch(new AnalyticsRebuildRequested($tenant->toString()));
    }

    public function acknowledge(TenantId $tenant): void
    {
        $this->cache->deleteItem($this->key($tenant));
    }

    private function key(TenantId $tenant): string
    {
        return 'analytics_rebuild.pending.'.$tenant->toString();
    }
}
