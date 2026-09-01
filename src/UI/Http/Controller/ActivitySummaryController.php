<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Activity\ActivitySummary;
use App\Domain\Activity\ActivityReport;
use App\Domain\Activity\ProjectActivity;
use App\Domain\Authorization\AccessDeniedException;
use App\Domain\User\User;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * US-059 (T-059-02) — API de synthèse d'activité, **strictement cloisonnée au collaborateur courant**
 * (CA-4) : toute demande visant un autre `user_id` est refusée (403), même sur un projet partagé. État
 * vide explicite (CA-3, jamais de 500). Le planning à venir est dégradé tant qu'US-037 (affectation)
 * n'est pas livrée.
 */
final class ActivitySummaryController extends AbstractController
{
    private const int DEFAULT_WEEKS = 4;
    private const int MAX_WEEKS = 12;

    public function __construct(
        private readonly ActivitySummary $summary,
        private readonly ClockInterface $clock,
    ) {
    }

    #[Route('/api/activity-summary', name: 'api_activity_summary', methods: ['GET'])]
    public function summary(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $requested = $request->query->get('user_id');
        if (is_string($requested) && '' !== $requested && $requested !== $user->id()) {
            throw new AccessDeniedException('La synthèse d\'activité est strictement personnelle.');
        }

        $weeks = $request->query->getInt('weeks', self::DEFAULT_WEEKS);
        $weeks = max(1, min($weeks, self::MAX_WEEKS));

        $report = $this->summary->forUser($user->tenantId(), $user->id(), $this->clock->now(), $weeks);

        return new JsonResponse($this->present($report));
    }

    /**
     * @return array<string, mixed>
     */
    private function present(ActivityReport $report): array
    {
        $total = $report->totalMinutes();

        return [
            'period' => ['start' => $report->periodStart->format('Y-m-d'), 'end' => $report->periodEnd->format('Y-m-d')],
            'empty' => $report->isEmpty(),
            'totalMinutes' => $total,
            'productionMinutes' => $report->productionMinutes,
            'absenceMinutes' => $report->absenceMinutes,
            'expectedMinutes' => $report->expectedMinutes,
            'occupationRate' => round($report->occupationRate(), 2),
            'byProject' => array_map(
                static fn (ProjectActivity $p): array => [
                    'projectId' => $p->projectId,
                    'label' => $p->label,
                    'minutes' => $p->minutes,
                    'share' => $total > 0 ? round($p->minutes / $total, 3) : 0.0,
                ],
                $report->byProject,
            ),
            'byType' => $report->byType,
            // US-037 non livrée : le planning à venir est signalé indisponible plutôt qu'omis (CA-3).
            'planning' => ['available' => false, 'message' => 'Le module d\'affectation n\'est pas encore activé.'],
        ];
    }
}
