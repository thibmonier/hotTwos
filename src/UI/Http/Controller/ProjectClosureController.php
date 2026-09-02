<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Project\ManageProjectClosure;
use App\Domain\Project\ProjectException;
use App\Domain\User\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use DateTimeImmutable;

/**
 * US-038 (T-038-05) — clôture opérationnelle et réouverture 4-eyes (POST-Redirect-Get + CSRF).
 * Renvoie vers l'onglet Clôture. Prérequis et habilitations portés par le cas d'usage.
 */
final class ProjectClosureController extends AbstractController
{
    public function __construct(private readonly ManageProjectClosure $closure)
    {
    }

    #[Route('/projets/{id}/cloture', name: 'project_close', requirements: ['id' => '[0-9a-f-]{36}'], methods: ['POST'])]
    public function close(#[CurrentUser] User $user, string $id, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('project_closure', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->toClosure($id);
        }

        try {
            $this->closure->close($user, $id, $request->request->has('confirmWarnings'));
            $this->addFlash('success', 'Projet clôturé.');
        } catch (ProjectException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->toClosure($id);
    }

    #[Route('/projets/{id}/reouverture', name: 'project_reopening_request', requirements: ['id' => '[0-9a-f-]{36}'], methods: ['POST'])]
    public function requestReopening(#[CurrentUser] User $user, string $id, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('project_closure', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->toClosure($id);
        }

        try {
            $this->closure->requestReopening($user, $id, trim((string) $request->request->get('reason')));
            $this->addFlash('success', 'Demande de réouverture enregistrée — en attente d\'approbation d\'un administrateur.');
        } catch (ProjectException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->toClosure($id);
    }

    #[Route('/projets/{id}/reouverture/{reopeningId}/approbation', name: 'project_reopening_approve', requirements: ['id' => '[0-9a-f-]{36}', 'reopeningId' => '[0-9a-f-]{36}'], methods: ['POST'])]
    public function approveReopening(#[CurrentUser] User $user, string $id, string $reopeningId, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('project_closure', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->toClosure($id);
        }

        $openUntil = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $request->request->get('openUntil'));
        if (false === $openUntil) {
            $this->addFlash('error', 'Date de fin de fenêtre requise.');

            return $this->toClosure($id);
        }

        try {
            $this->closure->approveReopening($user, $id, $reopeningId, $openUntil);
            $this->addFlash('success', 'Réouverture approuvée.');
        } catch (ProjectException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->toClosure($id);
    }

    private function toClosure(string $id): RedirectResponse
    {
        return $this->redirectToRoute('project_show', ['id' => $id, '_fragment' => 'panel-closure']);
    }
}
