<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Staffing\ViewWorkloadPlan;
use App\Domain\User\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * US-041 (EPIC-004) — plan de charge : capacité vs charge ferme par collaborateur. Le read service
 * applique le gating (VIEW_TEAM_COMPLETENESS) et n'expose aucun coût (HAB-1).
 */
final class WorkloadPlanController extends AbstractController
{
    public function __construct(
        private readonly ViewWorkloadPlan $plan,
        private readonly ClockInterface $clock,
    ) {
    }

    #[Route('/planification/charge', name: 'workload_plan', methods: ['GET'])]
    public function index(#[CurrentUser] User $user, Request $request): Response
    {
        $period = (string) $request->query->get('period', $this->clock->now()->format('Y-m'));
        if (1 !== preg_match('/^\d{4}-\d{2}$/', $period)) {
            $period = $this->clock->now()->format('Y-m');
        }

        return $this->render('planification/workload.html.twig', [
            'plan' => $this->plan->forMonth($user, $period),
        ]);
    }
}
