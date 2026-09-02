<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Project\ManageExternalCommitments;
use App\Domain\Project\CommitmentStatus;
use App\Domain\Project\CommitmentType;
use App\Domain\Project\ProjectException;
use App\Domain\User\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * US-034 (T-034-04) — création d'un engagement externe (POST-Redirect-Get + CSRF). Renvoie vers
 * l'onglet Engagements du détail projet. Règles portées par le cas d'usage (montant/fournisseur
 * obligatoires, refus si projet clôturé).
 */
final class ProjectCommitmentController extends AbstractController
{
    public function __construct(private readonly ManageExternalCommitments $commitments)
    {
    }

    #[Route('/projets/{id}/engagements', name: 'project_commitment_add', requirements: ['id' => '[0-9a-f-]{36}'], methods: ['POST'])]
    public function create(#[CurrentUser] User $user, string $id, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('project_commitment', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->toCommitments($id);
        }

        $type = CommitmentType::tryFrom((string) $request->request->get('type'));
        $status = CommitmentStatus::tryFrom((string) $request->request->get('status'));
        $label = trim((string) $request->request->get('label'));
        $supplier = trim((string) $request->request->get('supplier'));
        $euros = filter_var($request->request->get('amountEuros'), \FILTER_VALIDATE_INT);

        if (!$type instanceof CommitmentType || !$status instanceof CommitmentStatus || '' === $label) {
            $this->addFlash('error', 'Engagement : type, statut et libellé requis.');

            return $this->toCommitments($id);
        }

        try {
            $this->commitments->create($user, $id, $type, $label, false !== $euros ? $euros * 100 : 0, $supplier, $status, null);
            $this->addFlash('success', 'Engagement externe ajouté.');
        } catch (ProjectException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->toCommitments($id);
    }

    private function toCommitments(string $id): RedirectResponse
    {
        return $this->redirectToRoute('project_show', ['id' => $id, '_fragment' => 'panel-commitments']);
    }
}
