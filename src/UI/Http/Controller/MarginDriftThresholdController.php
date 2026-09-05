<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Budget\MarginDriftThreshold;
use App\Domain\Budget\MarginDriftThresholdProvider;
use App\Domain\Budget\MarginDriftThresholdRepository;
use App\Domain\User\User;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * US-018 (T-018-03) — paramétrage du seuil de dérive de marge par tenant (override US-072).
 *
 * Paramétrage administrateur (deny-by-default via `MANAGE_ORGANIZATION`).
 */
final class MarginDriftThresholdController extends AbstractController
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly MarginDriftThresholdRepository $thresholds,
    ) {
    }

    #[Route('/finance/config-derive', name: 'margin_drift_config', methods: ['GET'])]
    public function edit(#[CurrentUser] User $user): Response
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_ORGANIZATION);

        return $this->render('finance/margin-drift-config.html.twig', [
            'threshold' => $this->thresholds->findForTenant($user->tenantId()),
            'default' => MarginDriftThresholdProvider::DEFAULT_POINTS,
        ]);
    }

    #[Route('/finance/config-derive', name: 'margin_drift_config_save', methods: ['POST'])]
    public function save(#[CurrentUser] User $user, Request $request): RedirectResponse
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_ORGANIZATION);
        if (!$this->isCsrfTokenValid('margin_drift_config', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('margin_drift_config');
        }

        $points = filter_var($request->request->get('points'), \FILTER_VALIDATE_INT);
        if (false === $points) {
            $this->addFlash('error', 'Le seuil doit être un entier (points).');

            return $this->redirectToRoute('margin_drift_config');
        }

        try {
            $existing = $this->thresholds->findForTenant($user->tenantId());
            if ($existing instanceof MarginDriftThreshold) {
                $existing->reconfigure($points);
                $this->thresholds->save($existing);
            } else {
                $this->thresholds->save(new MarginDriftThreshold($user->tenantId(), $points));
            }
            $this->addFlash('success', sprintf('Seuil de dérive enregistré : %d points.', $points));
        } catch (InvalidArgumentException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('margin_drift_config');
    }
}
