<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Project\ManageAssignments;
use App\Domain\Project\ProjectException;
use App\Domain\User\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use DateTimeImmutable;

/**
 * US-037 (T-037-05) — actions d'affectation et d'ouverture exceptionnelle (POST-Redirect-Get + CSRF).
 * Renvoie vers l'onglet Équipe du détail projet. Habilitations et règles portées par le cas d'usage.
 */
final class ProjectAssignmentController extends AbstractController
{
    public function __construct(private readonly ManageAssignments $assignments)
    {
    }

    #[Route('/projets/{id}/affectations', name: 'project_assignment_add', requirements: ['id' => '[0-9a-f-]{36}'], methods: ['POST'])]
    public function assign(#[CurrentUser] User $user, string $id, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('project_team', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->toTeam($id);
        }

        $userId = trim((string) $request->request->get('userId'));
        $role = trim((string) $request->request->get('role'));
        $plannedDays = filter_var($request->request->get('plannedDays'), \FILTER_VALIDATE_INT);

        if ('' === $userId || '' === $role || false === $plannedDays) {
            $this->addFlash('error', 'Affectation : collaborateur, rôle et charge requis.');

            return $this->toTeam($id);
        }

        try {
            $this->assignments->assign($user, $id, $userId, $role, $plannedDays, $this->date($request->request->get('startDate')), $this->date($request->request->get('endDate')));
            $this->addFlash('success', 'Collaborateur affecté.');
        } catch (ProjectException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->toTeam($id);
    }

    #[Route('/projets/{id}/affectations/{assignmentId}/retrait', name: 'project_assignment_remove', requirements: ['id' => '[0-9a-f-]{36}', 'assignmentId' => '[0-9a-f-]{36}'], methods: ['POST'])]
    public function remove(#[CurrentUser] User $user, string $id, string $assignmentId, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('project_team', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->toTeam($id);
        }

        try {
            $this->assignments->remove($user, $assignmentId);
            $this->addFlash('success', 'Affectation retirée.');
        } catch (ProjectException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->toTeam($id);
    }

    #[Route('/projets/{id}/ouvertures', name: 'project_opening_grant', requirements: ['id' => '[0-9a-f-]{36}'], methods: ['POST'])]
    public function grantOpening(#[CurrentUser] User $user, string $id, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('project_team', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->toTeam($id);
        }

        $userId = trim((string) $request->request->get('userId'));
        $week = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $request->request->get('weekStart'));
        $reason = trim((string) $request->request->get('reason'));

        if ('' === $userId || false === $week) {
            $this->addFlash('error', 'Ouverture : collaborateur et semaine requis.');

            return $this->toTeam($id);
        }

        try {
            $this->assignments->grantOpening($user, $id, $userId, $week, $reason);
            $this->addFlash('success', 'Ouverture exceptionnelle accordée.');
        } catch (ProjectException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->toTeam($id);
    }

    private function date(mixed $raw): ?DateTimeImmutable
    {
        if (is_string($raw) && '' !== $raw) {
            $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $raw);
            if (false !== $parsed) {
                return $parsed;
            }
        }

        return null;
    }

    private function toTeam(string $id): RedirectResponse
    {
        return $this->redirectToRoute('project_show', ['id' => $id, '_fragment' => 'panel-team']);
    }
}
