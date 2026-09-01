<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Completeness\CompletenessGrid;
use App\Application\Completeness\CompletenessScope;
use App\Domain\Completeness\WeekCompleteness;
use App\Domain\User\User;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * US-058 — API de complétude de saisie (grille collaborateur × semaine). Périmètre « soi-même » par
 * défaut ; `?scope=team` exige `VIEW_TEAM_COMPLETENESS` (403). Export CSV protégé contre l'injection.
 */
final class CompletenessController extends AbstractController
{
    private const int WEEKS = 4;

    public function __construct(
        private readonly CompletenessScope $scope,
        private readonly CompletenessGrid $grid,
        private readonly ClockInterface $clock,
    ) {
    }

    #[Route('/api/completude', name: 'api_completeness', methods: ['GET'])]
    public function grid(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        return new JsonResponse(array_map(
            static fn (WeekCompleteness $w): array => [
                'userId' => $w->userId,
                'week' => $w->weekStart->format('Y-m-d'),
                'expected' => $w->expectedDays,
                'filled' => $w->filledDays,
                'rate' => round($w->rate(), 2),
                'state' => $w->state->value,
            ],
            $this->build($user, $request),
        ));
    }

    #[Route('/api/completude/export', name: 'api_completeness_export', methods: ['GET'])]
    public function export(#[CurrentUser] User $user, Request $request): Response
    {
        $lines = ['Collaborateur;Semaine;Attendus;Saisis;Taux;Statut'];
        foreach ($this->build($user, $request) as $w) {
            $lines[] = implode(';', [
                $this->csvSafe($w->userId),
                $w->weekStart->format('Y-m-d'),
                (string) $w->expectedDays,
                (string) $w->filledDays,
                number_format($w->rate() * 100, 0).'%',
                $this->csvSafe($w->state->value),
            ]);
        }

        return new Response(implode("\r\n", $lines)."\r\n", Response::HTTP_OK, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="completude.csv"',
        ]);
    }

    /**
     * @return list<WeekCompleteness>
     */
    private function build(User $user, Request $request): array
    {
        $userIds = $this->scope->resolve($user, 'team' === $request->query->getString('scope'));

        return $this->grid->build($user->tenantId(), $userIds, $this->clock->now(), self::WEEKS);
    }

    /**
     * Neutralise l'injection CSV : un champ commençant par =, +, -, @ (ou espaces/tab) est préfixé
     * d'une apostrophe pour ne pas être interprété comme une formule par un tableur.
     */
    private function csvSafe(string $value): string
    {
        return 1 === preg_match('/^[=+\-@\t\r ]/', $value) ? "'".$value : $value;
    }
}
