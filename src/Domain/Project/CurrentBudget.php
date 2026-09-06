<?php

declare(strict_types=1);

namespace App\Domain\Project;

/**
 * Budget courant d'un projet (US-033) : coût (charge) et montant (CA) après application des avenants.
 * `null` = dimension non budgétée.
 */
final readonly class CurrentBudget
{
    public function __construct(
        public ?int $costCents,
        public ?int $revenueCents,
    ) {
    }
}
