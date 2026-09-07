<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Calendar\ClosurePeriod;
use App\Domain\Calendar\ClosurePeriodRepository;
use App\Domain\User\User;
use DateTimeImmutable;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * US-022 (EF-REF-9) — paramétrage des périodes de fermeture entreprise. Réservé aux administrateurs
 * (`MANAGE_ORGANIZATION`).
 */
final class ClosurePeriodController extends AbstractController
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly ClosurePeriodRepository $closures,
    ) {
    }

    #[Route('/parametrage/fermetures', name: 'closure_index', methods: ['GET'])]
    public function index(#[CurrentUser] User $user): Response
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_ORGANIZATION);

        return $this->render('parametrage/closures.html.twig', [
            'closures' => $this->closures->findForTenant($user->tenantId()),
        ]);
    }

    #[Route('/parametrage/fermetures', name: 'closure_add', methods: ['POST'])]
    public function add(#[CurrentUser] User $user, Request $request): RedirectResponse
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_ORGANIZATION);
        if (!$this->isCsrfTokenValid('closure_add', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('closure_index');
        }

        $start = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $request->request->get('start'));
        $end = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $request->request->get('end'));
        $label = trim((string) $request->request->get('label'));
        if (false === $start || false === $end || '' === $label) {
            $this->addFlash('error', 'Dates (AAAA-MM-JJ) et libellé sont obligatoires.');

            return $this->redirectToRoute('closure_index');
        }

        try {
            $this->closures->save(new ClosurePeriod($user->tenantId(), $start, $end, $label));
            $this->addFlash('success', sprintf('Fermeture « %s » ajoutée.', $label));
        } catch (InvalidArgumentException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('closure_index');
    }

    #[Route('/parametrage/fermetures/{id}/suppression', name: 'closure_delete', requirements: ['id' => '[0-9a-f-]{36}'], methods: ['POST'])]
    public function delete(#[CurrentUser] User $user, string $id, Request $request): RedirectResponse
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_ORGANIZATION);
        if (!$this->isCsrfTokenValid('closure_delete', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('closure_index');
        }

        $closure = $this->closures->find($user->tenantId(), $id);
        if ($closure instanceof ClosurePeriod) {
            $this->closures->delete($closure);
            $this->addFlash('success', 'Fermeture supprimée.');
        }

        return $this->redirectToRoute('closure_index');
    }
}
