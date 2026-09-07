<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Authorization\Authorizer;
use App\Application\Authorization\DefaultRoleMatrix;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\RoleDefinition;
use App\Domain\User\User;
use App\Domain\Validation\AbsenceValidationCircuit;
use App\Domain\Validation\AbsenceValidationCircuitRepository;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * US-017 (EF-REF-25) — paramétrage du circuit de validation des absences (1 ou 2 étapes, rôle validateur
 * par étape). Réservé aux administrateurs (`MANAGE_ORGANIZATION`). Sans circuit, la validation reste
 * mono-étape (comportement historique).
 */
final class ValidationCircuitController extends AbstractController
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly AbsenceValidationCircuitRepository $circuits,
    ) {
    }

    #[Route('/parametrage/circuits-validation', name: 'validation_circuit_index', methods: ['GET'])]
    public function index(#[CurrentUser] User $user): Response
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_ORGANIZATION);

        $circuit = $this->circuits->findForTenant($user->tenantId());

        return $this->render('parametrage/validation-circuit.html.twig', [
            'steps' => $circuit instanceof AbsenceValidationCircuit ? $circuit->steps() : [],
            'roles' => array_map(static fn (RoleDefinition $d): string => $d->name, DefaultRoleMatrix::definitions()),
        ]);
    }

    #[Route('/parametrage/circuits-validation', name: 'validation_circuit_save', methods: ['POST'])]
    public function save(#[CurrentUser] User $user, Request $request): RedirectResponse
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_ORGANIZATION);
        if (!$this->isCsrfTokenValid('validation_circuit_save', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('validation_circuit_index');
        }

        $steps = array_values(array_filter([
            trim((string) $request->request->get('step1')),
            trim((string) $request->request->get('step2')),
        ], static fn (string $r): bool => '' !== $r));

        try {
            $existing = $this->circuits->findForTenant($user->tenantId());
            if ($existing instanceof AbsenceValidationCircuit) {
                $existing->reconfigure($steps);
                $this->circuits->save($existing);
            } else {
                $this->circuits->save(new AbsenceValidationCircuit($user->tenantId(), $steps));
            }
            $this->addFlash('success', 'Circuit de validation enregistré.');
        } catch (InvalidArgumentException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('validation_circuit_index');
    }
}
