<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Authorization\Authorizer;
use App\Application\Budget\ViewProjectBudgetTracking;
use App\Domain\Authorization\Permission;
use App\Domain\Project\ProjectRepository;
use App\Domain\User\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * US-099 (DSH-PRJ) — tableau de bord des projets du responsable : KPI et projets en dérive.
 *
 * Agrège le suivi budgétaire par projet ({@see ViewProjectBudgetTracking}, US-072/036) — aucun nouveau
 * calcul. Réservé à `VIEW_PROJECT_FINANCIALS` (deny-by-default, 403 sinon) ; la dérive de **charge**
 * (atterrissage, OBJ-2) est visible sans coût, la dérive de **marge** l'est pour les rôles finance (HAB-1).
 */
final class ProjectDashboardController extends AbstractController
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly ProjectRepository $projects,
        private readonly ViewProjectBudgetTracking $budgetTracking,
    ) {
    }

    #[Route('/projets/tableau-de-bord', name: 'project_dashboard', methods: ['GET'])]
    public function dashboard(#[CurrentUser] User $user): Response
    {
        $this->authorizer->ensureCan($user, Permission::VIEW_PROJECT_FINANCIALS);

        $counters = ['total' => 0, 'drifting' => 0, 'escalated' => 0, 'noBudget' => 0];
        $alerts = [];
        foreach ($this->projects->findByResponsible($user->tenantId(), $user->id()) as $project) {
            $view = $this->budgetTracking->forProject($user, $project->id());
            $drifting = $view->isDrifting || $view->landingEarlyDrift || $view->landingEscalated;

            ++$counters['total'];
            if ($drifting) {
                ++$counters['drifting'];
            }
            if ($view->landingEscalated) {
                ++$counters['escalated'];
            }
            if (!$view->hasBudget) {
                ++$counters['noBudget'];
            }

            if ($drifting) {
                $alerts[] = [
                    'id' => $project->id(),
                    'name' => $view->projectName,
                    'marginDrift' => $view->isDrifting,
                    'chargeDrift' => $view->landingEarlyDrift,
                    'escalated' => $view->landingEscalated,
                    'overrunPercent' => $view->landingOverrunPercent,
                    'consumptionPercent' => $view->landingConsumptionPercent,
                ];
            }
        }

        return $this->render('project/dashboard.html.twig', [
            'counters' => $counters,
            'alerts' => $alerts,
        ]);
    }
}
