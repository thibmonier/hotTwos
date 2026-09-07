<?php

declare(strict_types=1);

namespace App\Application\Staffing;

/**
 * US-041 — plan de charge (capacité vs charge ferme) sur une période. La charge probable (pipeline
 * pondéré, INV-5) est hors périmètre tant qu'EPIC-006 (CRM) n'est pas livré.
 */
final readonly class WorkloadPlanView
{
    /**
     * @param list<WorkloadLine> $lines
     */
    public function __construct(
        public string $period,
        public array $lines,
    ) {
    }
}
