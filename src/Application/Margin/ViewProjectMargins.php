<?php

declare(strict_types=1);

namespace App\Application\Margin;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Margin\MarginCalculator;
use App\Domain\Margin\ProjectMargin;
use App\Domain\Margin\ProjectMarginRepository;
use App\Domain\User\User;

/**
 * Lecture gated des marges figées d'une période (US-071, T-071-06, CA-5).
 *
 * L'accès à la vue exige {@see Permission::VIEW_PROJECT_FINANCIALS} (sinon 403, ARC-19). Le coût et la
 * marge sont réservés à {@see Permission::VIEW_COLLABORATOR_COST} (HAB-1) : un chef de projet non
 * habilité voit le CA reconnu mais pas le coût/la marge. Toute lecture effective du coût/marge est
 * tracée (HAB-6). Le taux de marge provient du moteur unique {@see MarginCalculator} (ARC-6).
 */
final readonly class ViewProjectMargins
{
    public function __construct(
        private Authorizer $authorizer,
        private ProjectMarginRepository $margins,
        private MarginCalculator $calculator,
    ) {
    }

    public function forPeriod(User $user, string $period): ProjectMarginReport
    {
        $this->authorizer->ensureCan($user, Permission::VIEW_PROJECT_FINANCIALS);

        $costVisible = $this->authorizer->can($user, Permission::VIEW_COLLABORATOR_COST);
        if ($costVisible) {
            $this->authorizer->authorizeSensitiveRead($user, Permission::VIEW_COLLABORATOR_COST, 'margin:period:'.$period);
        }

        $frozen = $this->margins->findForPeriod($user->tenantId(), $period);

        $rows = array_map(
            fn (ProjectMargin $margin): ProjectMarginRow => $this->toRow($margin, $costVisible),
            $frozen,
        );

        $totalRevenue = array_sum(array_map(static fn (ProjectMargin $m): int => $m->revenueCents(), $frozen));
        $totalMargin = $costVisible
            ? array_sum(array_map(static fn (ProjectMargin $m): int => $m->marginCents(), $frozen))
            : null;
        $hasPartial = array_any($frozen, static fn (ProjectMargin $m): bool => $m->isPartial());

        return new ProjectMarginReport($period, $rows, $costVisible, $totalRevenue, $totalMargin, $hasPartial);
    }

    private function toRow(ProjectMargin $margin, bool $costVisible): ProjectMarginRow
    {
        return new ProjectMarginRow(
            $margin->projectRef(),
            $margin->projectName(),
            $margin->revenueCents(),
            $costVisible ? $margin->costCents() : null,
            $costVisible ? $margin->marginCents() : null,
            $costVisible ? $this->calculator->marginRatePercent($margin->revenueCents(), $margin->costCents()) : null,
            $margin->isPartial(),
            $margin->unvaluedCount(),
        );
    }
}
