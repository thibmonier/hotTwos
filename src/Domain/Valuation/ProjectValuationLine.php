<?php

declare(strict_types=1);

namespace App\Domain\Valuation;

/**
 * US-060 (T-060-04) — ligne de ventilation de la valorisation par projet.
 *
 * Agrège les valorisations figées (`valued`) d'un même projet : CA reconnu, coût réel et marge.
 * Le coût provient des snapshots {@see TimeEntryValuation} (non-rétroactivité conservée), le rattachement
 * au projet du join `time_entry_valuation ↔ time_entry` (le snapshot ne dénormalise pas `project_id`).
 */
final readonly class ProjectValuationLine
{
    public function __construct(
        public string $projectId,
        public string $projectName,
        public int $valuedCount,
        public int $revenueCents,
        public int $costCents,
    ) {
    }

    public function marginCents(): int
    {
        return $this->revenueCents - $this->costCents;
    }
}
