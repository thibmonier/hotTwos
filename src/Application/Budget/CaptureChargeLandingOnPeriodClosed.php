<?php

declare(strict_types=1);

namespace App\Application\Budget;

use App\Application\Period\Message\PeriodClosed;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * US-079c (EF-PRJ-16) — capture l'atterrissage en charge à la clôture de période.
 *
 * Handler **distinct** de {@see \App\Application\Margin\FreezeProjectMarginsOnPeriodClosed} : les deux
 * réagissent au même {@see PeriodClosed} sans se coupler. L'historisation n'alourdit pas le figeage de
 * marge (décision rétro S14).
 */
#[AsMessageHandler]
final readonly class CaptureChargeLandingOnPeriodClosed
{
    public function __construct(private CaptureChargeLandingSnapshots $capture)
    {
    }

    public function __invoke(PeriodClosed $message): void
    {
        $this->capture->forClosedPeriod($message->tenantId(), $message->period());
    }
}
