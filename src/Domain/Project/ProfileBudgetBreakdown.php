<?php

declare(strict_types=1);

namespace App\Domain\Project;

/**
 * Résultat de la valorisation d'un budget de charge par profil (US-078) : total jours et équivalents
 * € de vente / € de coût aux taux de la date de référence. `missingProfileIds` liste les profils sans
 * taux en vigueur (non comptés à tort comme 0, CA-4).
 */
final readonly class ProfileBudgetBreakdown
{
    /**
     * @param list<string> $missingProfileIds
     */
    public function __construct(
        public int $days,
        public int $sellingCents,
        public int $costCents,
        public array $missingProfileIds,
    ) {
    }

    public function hasMissingRate(): bool
    {
        return [] !== $this->missingProfileIds;
    }
}
