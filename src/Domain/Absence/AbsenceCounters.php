<?php

declare(strict_types=1);

namespace App\Domain\Absence;

/**
 * Compteurs d'absences d'un collaborateur (US-054, EF-TMP-16). Objet de lecture pur.
 *
 * `solde` = acquis − pris (validés). `projeté` = solde − en attente (si toutes les demandes en
 * attente étaient approuvées). Les demandes refusées ne comptent pas.
 */
final readonly class AbsenceCounters
{
    public function __construct(
        public float $acquired,
        public float $taken,
        public float $pending,
    ) {
    }

    public function balance(): float
    {
        return $this->acquired - $this->taken;
    }

    public function projectedBalance(): float
    {
        return $this->balance() - $this->pending;
    }
}
