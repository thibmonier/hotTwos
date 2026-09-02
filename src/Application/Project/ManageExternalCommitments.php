<?php

declare(strict_types=1);

namespace App\Application\Project;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\SecurityAuditLogger;
use App\Domain\Project\CommitmentStatus;
use App\Domain\Project\CommitmentType;
use App\Domain\Project\ExternalCommitment;
use App\Domain\Project\ExternalCommitmentRepository;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectException;
use App\Domain\Project\ProjectRepository;
use App\Domain\Project\ProjectStatus;
use App\Domain\User\User;

/**
 * Gestion des engagements externes d'un projet (US-034, EF-PRJ-10). Habilitation `EDIT_PROJECT`.
 * Montant et fournisseur obligatoires (CA-6, portés par l'entité) ; aucun engagement ne peut être créé
 * sur un projet **clôturé** (CA-5, RG-PRJ-5). Les engagements alimentent la marge (coûts externes) —
 * le budget de vente complet relève d'US-033 (dégradé).
 */
final readonly class ManageExternalCommitments
{
    public function __construct(
        private Authorizer $authorizer,
        private ProjectRepository $projects,
        private ExternalCommitmentRepository $commitments,
        private SecurityAuditLogger $audit,
    ) {
    }

    public function create(
        User $user,
        string $projectId,
        CommitmentType $type,
        string $label,
        int $amountCents,
        string $supplier,
        CommitmentStatus $status,
        ?string $lotId,
    ): ExternalCommitment {
        $this->authorizer->ensureCan($user, Permission::EDIT_PROJECT);
        $tenant = $user->tenantId();

        $project = $this->projects->find($tenant, $projectId);
        if (!$project instanceof Project) {
            throw new ProjectException('Projet introuvable.');
        }
        if (ProjectStatus::CLOTURE === $project->status()) {
            throw new ProjectException('Impossible de créer un engagement sur un projet clôturé (RG-PRJ-5).');
        }

        $commitment = new ExternalCommitment($tenant, $projectId, $type, $label, $amountCents, $supplier, $status, $lotId);
        $this->commitments->save($commitment);
        $this->audit->record('project_commitment_added', $tenant->toString(), $user->getUserIdentifier(), [
            'project' => $project->code(),
            'commitment' => $commitment->id(),
        ]);

        return $commitment;
    }

    /** Total des coûts externes engagés d'un projet, en centimes. */
    public function totalExternalCents(User $user, string $projectId): int
    {
        $total = 0;
        foreach ($this->commitments->findForProject($user->tenantId(), $projectId) as $commitment) {
            $total += $commitment->amountCents();
        }

        return $total;
    }
}
