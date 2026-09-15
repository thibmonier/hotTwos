<?php

declare(strict_types=1);

namespace App\Application\Absence;

use App\Domain\Absence\AbsenceCounters;
use App\Domain\Absence\AbsenceRequestRepository;
use App\Domain\Absence\AbsenceStatus;
use App\Domain\Calendar\WorkingDaysCalculator;
use App\Domain\Tenant\TenantId;

/**
 * Solde d'absences d'un collaborateur.
 *
 * US-107 — base de décompte **unique** : les jours pris/en attente sont comptés en **jours ouvrés**
 * (via {@see WorkingDaysCalculator}, régime du collaborateur inclus), comme la projection d'impact
 * (US-091b, /api/absences/impact). Un week-end, un férié ou une fermeture ne décompte plus de solde.
 */
final readonly class AbsenceBalance
{
    public function __construct(
        private AbsenceRequestRepository $requests,
        private WorkingDaysCalculator $calculator,
        private float $acquiredDays,
    ) {
    }

    public function for(TenantId $tenant, string $userId): AbsenceCounters
    {
        $taken = 0.0;
        $pending = 0.0;

        foreach ($this->requests->findForUser($tenant, $userId) as $request) {
            $status = $request->status();
            if (AbsenceStatus::REJECTED === $status) {
                continue;
            }

            // Borne haute exclue : endDate incluse ⇒ +1 jour.
            $days = (float) $this->calculator->workingDaysForUser(
                $tenant,
                $userId,
                $request->startDate(),
                $request->endDate()->modify('+1 day'),
            );

            if (AbsenceStatus::VALIDATED === $status) {
                $taken += $days;
            } else {
                $pending += $days;
            }
        }

        return new AbsenceCounters($this->acquiredDays, $taken, $pending);
    }
}
