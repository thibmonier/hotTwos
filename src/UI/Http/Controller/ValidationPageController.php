<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Project\ProjectRepository;
use App\Domain\Timesheet\TimeEntry;
use App\Domain\Timesheet\TimeEntryRepository;
use App\Domain\User\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * US-055 — écran de validation par lot (adaptateur web). Présente au chef de projet les
 * imputations **en attente** sur ses projets ; la décision passe par l'API
 * POST /api/time-entries/validate (contrôleur Stimulus), où l'habilitation est aussi vérifiée.
 *
 * Réservé aux porteurs de VALIDATE_TIME (deny-by-default, règle 11 ; parité avec la nav filtrée
 * `validate:time` et avec /valorisation) : un utilisateur non habilité obtient une 403 habillée.
 */
final class ValidationPageController extends AbstractController
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly ProjectRepository $projects,
        private readonly TimeEntryRepository $entries,
    ) {
    }

    #[Route('/validation', name: 'timesheet_validation', methods: ['GET'])]
    public function pending(#[CurrentUser] User $user): Response
    {
        $this->authorizer->ensureCan($user, Permission::VALIDATE_TIME);

        $myProjects = $this->projects->findByResponsible($user->tenantId(), $user->id());
        $projectNames = [];
        foreach ($myProjects as $project) {
            $projectNames[$project->id()] = $project->name();
        }

        $pending = $this->entries->findPendingForProjects($user->tenantId(), array_keys($projectNames));

        $rows = array_map(
            static fn (TimeEntry $entry): array => [
                'id' => $entry->id(),
                'project' => $projectNames[$entry->projectId()] ?? $entry->projectId(),
                'date' => $entry->workDate()->format('Y-m-d'),
                'minutes' => $entry->minutes(),
            ],
            $pending,
        );

        return $this->render('timesheet/validation.html.twig', [
            'rows' => $rows,
            'hasProjects' => [] !== $myProjects,
        ]);
    }
}
