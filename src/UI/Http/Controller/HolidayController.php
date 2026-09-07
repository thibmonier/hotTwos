<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Calendar\Holiday;
use App\Domain\Calendar\HolidayRepository;
use App\Domain\User\User;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * US-012 (EF-REF-6) — paramétrage des jours fériés du tenant (déclaration, liste, suppression).
 * Réservé aux administrateurs (`MANAGE_ORGANIZATION`, deny-by-default).
 */
final class HolidayController extends AbstractController
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly HolidayRepository $holidays,
    ) {
    }

    #[Route('/parametrage/jours-feries', name: 'holiday_index', methods: ['GET'])]
    public function index(#[CurrentUser] User $user): Response
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_ORGANIZATION);

        return $this->render('parametrage/holidays.html.twig', [
            'holidays' => $this->holidays->findForTenant($user->tenantId()),
        ]);
    }

    #[Route('/parametrage/jours-feries', name: 'holiday_add', methods: ['POST'])]
    public function add(#[CurrentUser] User $user, Request $request): RedirectResponse
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_ORGANIZATION);
        if (!$this->isCsrfTokenValid('holiday_add', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('holiday_index');
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $request->request->get('date'));
        $label = trim((string) $request->request->get('label'));
        if (false === $date || '' === $label) {
            $this->addFlash('error', 'Date (AAAA-MM-JJ) et libellé sont obligatoires.');

            return $this->redirectToRoute('holiday_index');
        }

        if ($this->holidays->existsForDate($user->tenantId(), $date)) {
            $this->addFlash('error', 'Ce jour férié existe déjà.');

            return $this->redirectToRoute('holiday_index');
        }

        $this->holidays->save(new Holiday($user->tenantId(), $date, $label));
        $this->addFlash('success', sprintf('Jour férié « %s » ajouté.', $label));

        return $this->redirectToRoute('holiday_index');
    }

    #[Route('/parametrage/jours-feries/{id}/suppression', name: 'holiday_delete', requirements: ['id' => '[0-9a-f-]{36}'], methods: ['POST'])]
    public function delete(#[CurrentUser] User $user, string $id, Request $request): RedirectResponse
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_ORGANIZATION);
        if (!$this->isCsrfTokenValid('holiday_delete', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('holiday_index');
        }

        $holiday = $this->holidays->find($user->tenantId(), $id);
        if ($holiday instanceof Holiday) {
            $this->holidays->delete($holiday);
            $this->addFlash('success', 'Jour férié supprimé.');
        }

        return $this->redirectToRoute('holiday_index');
    }
}
