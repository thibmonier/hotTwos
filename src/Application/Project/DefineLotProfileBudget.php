<?php

declare(strict_types=1);

namespace App\Application\Project;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Project\LotProfileBudget;
use App\Domain\Project\LotProfileBudgetRepository;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectException;
use App\Domain\Project\ProjectLot;
use App\Domain\Project\ProjectLotRepository;
use App\Domain\Project\ProjectRepository;
use App\Domain\User\User;

/**
 * Définit le budget de charge d'un profil sur un lot (US-078, EF-PRJ-9). Habilitation `EDIT_PROJECT` ;
 * refusé sur projet clôturé (RG-PRJ-5). Idempotent par couple (lot, profil) : redéfinir met à jour les jours.
 */
final readonly class DefineLotProfileBudget
{
    public function __construct(
        private Authorizer $authorizer,
        private ProjectRepository $projects,
        private ProjectLotRepository $lots,
        private LotProfileBudgetRepository $budgets,
    ) {
    }

    public function define(User $user, string $lotId, string $profileId, int $days): void
    {
        $this->authorizer->ensureCan($user, Permission::EDIT_PROJECT);
        $tenant = $user->tenantId();

        $lot = $this->lots->find($tenant, $lotId);
        if (!$lot instanceof ProjectLot) {
            throw new ProjectException('Lot introuvable.');
        }

        $project = $this->projects->find($tenant, $lot->projectId());
        if ($project instanceof Project) {
            $project->assertModifiable();
        }

        foreach ($this->budgets->findForLot($tenant, $lotId) as $existing) {
            if ($existing->profileId() === $profileId) {
                $existing->changeDays($days);
                $this->budgets->save($existing);

                return;
            }
        }

        $this->budgets->save(new LotProfileBudget($tenant, $lotId, $profileId, $days));
    }
}
