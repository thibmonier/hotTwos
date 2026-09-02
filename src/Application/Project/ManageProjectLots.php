<?php

declare(strict_types=1);

namespace App\Application\Project;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\SecurityAuditLogger;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectException;
use App\Domain\Project\ProjectLot;
use App\Domain\Project\ProjectLotRepository;
use App\Domain\Project\ProjectRepository;
use App\Domain\User\User;

/**
 * Gestion des lots d'un projet (US-031, EF-PRJ-2). Habilitation `EDIT_PROJECT`. Arborescence à
 * **2 niveaux** (un sous-lot ne peut avoir de parent qu'un lot racine). Le budget des **lots racines**
 * est comparé au budget projet : tout dépassement exige une **confirmation explicite** (CA-6). La
 * réallocation exige un **motif** et est tracée (CA-3).
 */
final readonly class ManageProjectLots
{
    public function __construct(
        private Authorizer $authorizer,
        private ProjectRepository $projects,
        private ProjectLotRepository $lots,
        private SecurityAuditLogger $audit,
    ) {
    }

    public function addLot(User $user, string $projectId, string $name, int $budgetDays, int $budgetCents, ?string $parentLotId, bool $confirmOverrun): ProjectLot
    {
        $this->authorizer->ensureCan($user, Permission::EDIT_PROJECT);
        $tenant = $user->tenantId();

        $project = $this->projects->find($tenant, $projectId);
        if (!$project instanceof Project) {
            throw new ProjectException('Projet introuvable.');
        }

        if (null !== $parentLotId) {
            $parent = $this->lots->find($tenant, $parentLotId);
            if (!$parent instanceof ProjectLot || $parent->projectId() !== $projectId) {
                throw new ProjectException('Lot parent introuvable.');
            }
            if (!$parent->isRoot()) {
                throw new ProjectException('Arborescence limitée à 2 niveaux : un sous-lot ne peut pas être parent (EF-PRJ-2).');
            }
        } else {
            // Lot racine : contrôle du dépassement du budget projet (CA-6).
            $this->guardRootOverrun($project, $projectId, $budgetCents, $confirmOverrun);
        }

        $lot = new ProjectLot($tenant, $projectId, $name, $budgetDays, $budgetCents, $parentLotId);
        $this->lots->save($lot);
        $this->audit->record('project_lot_added', $tenant->toString(), $user->getUserIdentifier(), ['project' => $project->code(), 'lot' => $lot->id()]);

        return $lot;
    }

    public function reallocate(User $user, string $lotId, int $budgetDays, int $budgetCents, string $reason): void
    {
        $this->authorizer->ensureCan($user, Permission::EDIT_PROJECT);
        if ('' === trim($reason)) {
            throw new ProjectException('Un motif est obligatoire pour réallouer un budget de lot (CA-3).');
        }

        $tenant = $user->tenantId();
        $lot = $this->lots->find($tenant, $lotId);
        if (!$lot instanceof ProjectLot) {
            throw new ProjectException('Lot introuvable.');
        }

        $lot->reallocateTo($budgetDays, $budgetCents);
        $this->lots->save($lot);
        $this->audit->record('project_lot_reallocated', $tenant->toString(), $user->getUserIdentifier(), ['lot' => $lotId, 'reason' => trim($reason)]);
    }

    private function guardRootOverrun(Project $project, string $projectId, int $addedCents, bool $confirmOverrun): void
    {
        $budget = $project->budgetCents();
        if (null === $budget) {
            return;
        }

        $rootSum = $addedCents;
        foreach ($this->lots->findForProject($project->tenantId(), $projectId) as $lot) {
            if ($lot->isRoot()) {
                $rootSum += $lot->budgetCents();
            }
        }

        if ($rootSum > $budget && !$confirmOverrun) {
            throw new ProjectException(sprintf('Confirmation requise : la somme des lots (%d €) dépasse le budget projet (%d €) (EF-PRJ-2).', intdiv($rootSum, 100), intdiv($budget, 100)));
        }
    }
}
