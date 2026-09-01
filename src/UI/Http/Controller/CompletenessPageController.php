<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Authorization\Authorizer;
use App\Application\Completeness\CompletenessGrid;
use App\Application\Completeness\CompletenessScope;
use App\Domain\Authorization\Permission;
use App\Domain\User\User;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * US-058 (T-058-04) — tableau de bord de complétude de saisie (adaptateur web).
 *
 * Grille collaborateurs × semaines à 4 états (code couleur + légende). Le périmètre s'adapte à
 * l'habilitation : équipe pour `VIEW_TEAM_COMPLETENESS`, sinon soi-même — sans erreur d'accès.
 */
final class CompletenessPageController extends AbstractController
{
    private const int WEEKS = 4;

    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly CompletenessScope $scope,
        private readonly CompletenessGrid $grid,
        private readonly ClockInterface $clock,
    ) {
    }

    #[Route('/completude', name: 'completeness_page', methods: ['GET'])]
    public function index(#[CurrentUser] User $user): Response
    {
        $team = $this->authorizer->can($user, Permission::VIEW_TEAM_COMPLETENESS);
        $userIds = $this->scope->resolve($user, $team);
        $cells = $this->grid->build($user->tenantId(), $userIds, $this->clock->now(), self::WEEKS);

        $weeks = [];
        /** @var array<string, array<string, array{state: string, rate: int}>> $rows */
        $rows = [];
        foreach ($cells as $cell) {
            $week = $cell->weekStart->format('Y-m-d');
            $weeks[$week] = true;
            $rows[$cell->userId][$week] = ['state' => $cell->state->value, 'rate' => (int) round($cell->rate() * 100)];
        }
        ksort($weeks);

        return $this->render('completeness/index.html.twig', [
            'team' => $team,
            'weeks' => array_keys($weeks),
            'rows' => $rows,
        ]);
    }
}
