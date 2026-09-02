<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Project\ManageMilestones;
use App\Application\Project\ManageProjectLots;
use App\Domain\Project\ProjectException;
use App\Domain\User\User;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use DateTimeImmutable;

/**
 * US-031 (T-031-05) — actions sur la structure d'un projet (lots & jalons), POST-Redirect-Get + CSRF.
 * Renvoie vers l'onglet Structure du détail projet. Habilitations et règles portées par les cas
 * d'usage (ARC-19).
 */
final class ProjectStructureController extends AbstractController
{
    public function __construct(
        private readonly ManageProjectLots $lots,
        private readonly ManageMilestones $milestones,
        private readonly ClockInterface $clock,
    ) {
    }

    #[Route('/projets/{id}/lots', name: 'project_lot_add', requirements: ['id' => '[0-9a-f-]{36}'], methods: ['POST'])]
    public function addLot(#[CurrentUser] User $user, string $id, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('project_structure', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->toStructure($id);
        }

        $name = trim((string) $request->request->get('name'));
        $days = filter_var($request->request->get('budgetDays'), \FILTER_VALIDATE_INT);
        $euros = filter_var($request->request->get('budgetEuros'), \FILTER_VALIDATE_INT);
        $parent = trim((string) $request->request->get('parentLotId'));

        if ('' === $name || false === $days || false === $euros) {
            $this->addFlash('error', 'Lot : nom, charge (jours) et montant (€) requis.');

            return $this->toStructure($id);
        }

        try {
            $this->lots->addLot($user, $id, $name, $days, $euros * 100, '' !== $parent ? $parent : null, $request->request->has('confirmOverrun'));
            $this->addFlash('success', sprintf('Lot « %s » ajouté.', $name));
        } catch (ProjectException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->toStructure($id);
    }

    #[Route('/projets/{id}/lots/{lotId}/reallocation', name: 'project_lot_reallocate', requirements: ['id' => '[0-9a-f-]{36}', 'lotId' => '[0-9a-f-]{36}'], methods: ['POST'])]
    public function reallocate(#[CurrentUser] User $user, string $id, string $lotId, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('project_structure', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->toStructure($id);
        }

        $days = filter_var($request->request->get('budgetDays'), \FILTER_VALIDATE_INT);
        $euros = filter_var($request->request->get('budgetEuros'), \FILTER_VALIDATE_INT);
        $reason = trim((string) $request->request->get('reason'));

        if (false === $days || false === $euros) {
            $this->addFlash('error', 'Réallocation : charge et montant requis.');

            return $this->toStructure($id);
        }

        try {
            $this->lots->reallocate($user, $lotId, $days, $euros * 100, $reason);
            $this->addFlash('success', 'Budget du lot réalloué.');
        } catch (ProjectException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->toStructure($id);
    }

    #[Route('/projets/{id}/jalons', name: 'project_milestone_add', requirements: ['id' => '[0-9a-f-]{36}'], methods: ['POST'])]
    public function addMilestone(#[CurrentUser] User $user, string $id, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('project_structure', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->toStructure($id);
        }

        $name = trim((string) $request->request->get('name'));
        $due = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $request->request->get('dueDate'));
        $billing = filter_var($request->request->get('billingEuros'), \FILTER_VALIDATE_INT);

        if ('' === $name || false === $due) {
            $this->addFlash('error', 'Jalon : nom et date prévisionnelle requis.');

            return $this->toStructure($id);
        }

        try {
            $this->milestones->addMilestone($user, $id, $name, $due, false !== $billing && $billing > 0 ? $billing * 100 : null);
            $this->addFlash('success', sprintf('Jalon « %s » ajouté.', $name));
        } catch (ProjectException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->toStructure($id);
    }

    #[Route('/projets/{id}/jalons/{milestoneId}/atteint', name: 'project_milestone_reach', requirements: ['id' => '[0-9a-f-]{36}', 'milestoneId' => '[0-9a-f-]{36}'], methods: ['POST'])]
    public function reachMilestone(#[CurrentUser] User $user, string $id, string $milestoneId, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('project_structure', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->toStructure($id);
        }

        try {
            $milestone = $this->milestones->markReached($user, $milestoneId, $this->clock->now());
            $this->addFlash('success', $milestone->hasBillingTrigger()
                ? 'Jalon atteint — facturation enregistrée (intention).'
                : 'Jalon atteint.');
        } catch (ProjectException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->toStructure($id);
    }

    private function toStructure(string $id): RedirectResponse
    {
        return $this->redirectToRoute('project_show', ['id' => $id, '_fragment' => 'panel-structure']);
    }
}
