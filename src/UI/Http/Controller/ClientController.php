<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Client\Client;
use App\Domain\Client\ClientRepository;
use App\Domain\User\User;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * US-014 (T-014-04) — référentiel des comptes clients (tranche minimale : liste + création).
 *
 * Paramétrage administrateur (deny-by-default via `MANAGE_ORGANIZATION`).
 */
final class ClientController extends AbstractController
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly ClientRepository $clients,
    ) {
    }

    #[Route('/clients', name: 'client_index', methods: ['GET'])]
    public function index(#[CurrentUser] User $user): Response
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_ORGANIZATION);

        return $this->render('client/index.html.twig', [
            'clients' => array_map(
                static fn (Client $c): array => ['name' => $c->name(), 'siren' => $c->siren()],
                $this->clients->findAllByTenant($user->tenantId()),
            ),
        ]);
    }

    #[Route('/clients', name: 'client_create', methods: ['POST'])]
    public function create(#[CurrentUser] User $user, Request $request): RedirectResponse
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_ORGANIZATION);
        if (!$this->isCsrfTokenValid('create_client', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('client_index');
        }

        $name = trim((string) $request->request->get('name'));
        $siren = trim((string) $request->request->get('siren'));

        try {
            $this->clients->save(new Client($user->tenantId(), $name, '' !== $siren ? $siren : null));
            $this->addFlash('success', sprintf('Client « %s » créé.', $name));
        } catch (InvalidArgumentException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('client_index');
    }
}
