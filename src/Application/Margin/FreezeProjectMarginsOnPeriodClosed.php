<?php

declare(strict_types=1);

namespace App\Application\Margin;

use App\Application\Period\Message\PeriodClosed;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Fige les marges par projet à la clôture d'une période (US-071, T-071-05, CA-1).
 *
 * À la consommation de {@see PeriodClosed}, calcule et fige la marge de chaque projet du mois à partir
 * des valorisations déjà figées (la valorisation est produite à la validation — US-060 ; la clôture
 * scelle des taux stables). Handler tenant-aware : le contexte tenant est posé par le middleware à la
 * consommation (parité worker, ARC-47). Coexiste avec {@see \App\Application\Period\TriggerDownstreamOnPeriodClosed}
 * sur le même message (handlers indépendants).
 */
#[AsMessageHandler]
final readonly class FreezeProjectMarginsOnPeriodClosed
{
    public function __construct(private ComputeProjectMargins $computeMargins)
    {
    }

    public function __invoke(PeriodClosed $message): void
    {
        $this->computeMargins->forClosedPeriod($message->tenantId(), $message->period());
    }
}
