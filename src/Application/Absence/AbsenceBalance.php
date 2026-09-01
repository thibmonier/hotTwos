<?php

declare(strict_types=1);

namespace App\Application\Absence;

use App\Domain\Absence\AbsenceCounters;
use App\Domain\Absence\AbsenceRequest;
use App\Domain\Absence\AbsenceRequestRepository;
use App\Domain\Absence\AbsenceStatus;
use App\Domain\Tenant\TenantId;

/**
 * Calcul des compteurs d'absences d'un collaborateur (US-054, EF-TMP-16).
 *
 * `pris` = somme des jours des demandes **validées** ; `en attente` = somme des jours des demandes
 * **en attente**. Les jours **acquis** sont paramétrés par tenant (accrual — modèle d'acquisition
 * simplifié : un droit annuel fixe pour cette itération).
 */
final readonly class AbsenceBalance
{
    public function __construct(
        private AbsenceRequestRepository $requests,
        private float $acquiredDays,
    ) {
    }

    public function for(TenantId $tenant, string $userId): AbsenceCounters
    {
        $taken = 0.0;
        $pending = 0.0;

        foreach ($this->requests->findForUser($tenant, $userId) as $request) {
            $this->accumulate($request, $taken, $pending);
        }

        return new AbsenceCounters($this->acquiredDays, $taken, $pending);
    }

    private function accumulate(AbsenceRequest $request, float &$taken, float &$pending): void
    {
        match ($request->status()) {
            AbsenceStatus::VALIDATED => $taken += $request->days(),
            AbsenceStatus::PENDING => $pending += $request->days(),
            AbsenceStatus::REJECTED => null,
        };
    }
}
