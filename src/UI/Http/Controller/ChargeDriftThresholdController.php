<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Budget\ChargeDriftThreshold;
use App\Domain\Budget\ChargeDriftThresholdProvider;
use App\Domain\Budget\ChargeDriftThresholdRepository;
use App\Domain\Project\ContractType;
use App\Domain\User\User;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * US-079b (EF-PRJ-15) — paramétrage du seuil de dérive de charge **par type de projet** (alerte + 2e
 * seuil d'escalade direction). Généralise les constantes OBJ-2. Réservé aux administrateurs
 * (`MANAGE_ORGANIZATION`, deny-by-default).
 */
final class ChargeDriftThresholdController extends AbstractController
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly ChargeDriftThresholdRepository $thresholds,
    ) {
    }

    #[Route('/finance/config-derive-charge', name: 'charge_drift_config', methods: ['GET'])]
    public function edit(#[CurrentUser] User $user): Response
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_ORGANIZATION);

        $configured = [];
        foreach ($this->thresholds->findForTenant($user->tenantId()) as $threshold) {
            $configured[$threshold->contractType()->value] = $threshold;
        }

        return $this->render('finance/charge-drift-config.html.twig', [
            'contractTypes' => ContractType::cases(),
            'configured' => $configured,
            'defaultAlert' => ChargeDriftThresholdProvider::DEFAULT_ALERT_PERCENT,
            'defaultEscalation' => ChargeDriftThresholdProvider::DEFAULT_ESCALATION_PERCENT,
        ]);
    }

    #[Route('/finance/config-derive-charge', name: 'charge_drift_config_save', methods: ['POST'])]
    public function save(#[CurrentUser] User $user, Request $request): RedirectResponse
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_ORGANIZATION);
        if (!$this->isCsrfTokenValid('charge_drift_config', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('charge_drift_config');
        }

        try {
            foreach (ContractType::cases() as $type) {
                $this->saveType($user, $request, $type);
            }
            $this->addFlash('success', 'Seuils de dérive de charge enregistrés.');
        } catch (InvalidArgumentException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('charge_drift_config');
    }

    private function saveType(User $user, Request $request, ContractType $type): void
    {
        $alert = filter_var($request->request->get('alert_'.$type->value), \FILTER_VALIDATE_FLOAT);
        $escalation = filter_var($request->request->get('escalation_'.$type->value), \FILTER_VALIDATE_FLOAT);
        if (false === $alert || false === $escalation) {
            throw new InvalidArgumentException('Les seuils doivent être des pourcentages valides.');
        }

        $existing = $this->thresholds->findFor($user->tenantId(), $type);
        if ($existing instanceof ChargeDriftThreshold) {
            $existing->reconfigure($alert, $escalation);
            $this->thresholds->save($existing);

            return;
        }

        $this->thresholds->save(new ChargeDriftThreshold($user->tenantId(), $type, $alert, $escalation));
    }
}
