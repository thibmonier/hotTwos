<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Calendar\WorkSchedule;
use App\Domain\Calendar\WorkScheduleRepository;
use App\Domain\User\User;
use App\Domain\User\UserRepository;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * US-021 (EF-REF-7) — paramétrage des régimes de travail (temps partiel par collaborateur). Réservé aux
 * administrateurs (`MANAGE_ORGANIZATION`). Sans régime, le collaborateur est à temps plein (Lun-Ven).
 */
final class WorkScheduleController extends AbstractController
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly WorkScheduleRepository $schedules,
        private readonly UserRepository $users,
    ) {
    }

    #[Route('/parametrage/regimes-travail', name: 'work_schedule_index', methods: ['GET'])]
    public function index(#[CurrentUser] User $user): Response
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_ORGANIZATION);
        $tenant = $user->tenantId();

        $byUser = [];
        foreach ($this->schedules->findForTenant($tenant) as $schedule) {
            $byUser[$schedule->userId()] = $schedule->workingWeekdays();
        }

        $userIds = $this->users->findIdsByTenant($tenant);

        return $this->render('parametrage/work-schedules.html.twig', [
            'collaborators' => $this->users->findDisplayNamesByIds($tenant, $userIds),
            'schedulesByUser' => $byUser,
            'weekdays' => [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi'],
        ]);
    }

    #[Route('/parametrage/regimes-travail', name: 'work_schedule_save', methods: ['POST'])]
    public function save(#[CurrentUser] User $user, Request $request): RedirectResponse
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_ORGANIZATION);
        if (!$this->isCsrfTokenValid('work_schedule_save', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('work_schedule_index');
        }

        $userId = (string) $request->request->get('collaborator');
        $weekdays = array_values(array_map(static fn (mixed $d): int => is_numeric($d) ? (int) $d : 0, $request->request->all('weekdays')));

        try {
            $existing = $this->schedules->find($user->tenantId(), $userId);
            if ($existing instanceof WorkSchedule) {
                $existing->reconfigure($weekdays);
                $this->schedules->save($existing);
            } else {
                $this->schedules->save(new WorkSchedule($user->tenantId(), $userId, $weekdays));
            }
            $this->addFlash('success', 'Régime de travail enregistré.');
        } catch (InvalidArgumentException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('work_schedule_index');
    }

    #[Route('/parametrage/regimes-travail/{userId}/suppression', name: 'work_schedule_delete', requirements: ['userId' => '[0-9a-f-]{36}'], methods: ['POST'])]
    public function delete(#[CurrentUser] User $user, string $userId, Request $request): RedirectResponse
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_ORGANIZATION);
        if (!$this->isCsrfTokenValid('work_schedule_delete', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('work_schedule_index');
        }

        $schedule = $this->schedules->find($user->tenantId(), $userId);
        if ($schedule instanceof WorkSchedule) {
            $this->schedules->delete($schedule);
            $this->addFlash('success', 'Régime supprimé (retour au temps plein).');
        }

        return $this->redirectToRoute('work_schedule_index');
    }
}
