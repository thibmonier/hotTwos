<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Authorization\Authorizer;
use App\Application\Project\ChangeProjectStatus;
use App\Application\Project\CreateProject;
use App\Domain\Authorization\Permission;
use App\Domain\Project\ContractType;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectException;
use App\Domain\Project\ProjectRepository;
use App\Domain\Project\ProjectStatus;
use App\Domain\User\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use DateTimeImmutable;

/**
 * US-030 (T-030-06) — écran de gestion des projets (adaptateur web). Liste, création (RG-PRJ-1) et
 * détail avec cycle de vie (transitions autorisées uniquement). Habilitations vérifiées en applicatif
 * (ARC-19). POST-Redirect-Get + CSRF. Le détail expose les onglets du module (structure, équipe,
 * engagements, clôture) enrichis par les US suivantes.
 */
final class ProjectPageController extends AbstractController
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly ProjectRepository $projects,
        private readonly CreateProject $createProject,
        private readonly ChangeProjectStatus $changeProjectStatus,
    ) {
    }

    #[Route('/projets', name: 'project_index', methods: ['GET'])]
    public function index(#[CurrentUser] User $user): Response
    {
        $this->authorizer->ensureCan($user, Permission::VIEW_PROJECT);

        return $this->render('project/index.html.twig', [
            'projects' => array_map(
                $this->row(...),
                $this->projects->findAllByTenant($user->tenantId()),
            ),
            'canCreate' => $this->authorizer->can($user, Permission::CREATE_PROJECT),
        ]);
    }

    #[Route('/projets/nouveau', name: 'project_new', methods: ['GET'])]
    public function new(#[CurrentUser] User $user): Response
    {
        $this->authorizer->ensureCan($user, Permission::CREATE_PROJECT);

        return $this->render('project/new.html.twig', ['contractTypes' => ContractType::cases()]);
    }

    #[Route('/projets', name: 'project_create', methods: ['POST'])]
    public function create(#[CurrentUser] User $user, Request $request): RedirectResponse
    {
        $this->authorizer->ensureCan($user, Permission::CREATE_PROJECT);
        if (!$this->isCsrfTokenValid('create_project', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('project_new');
        }

        $name = trim((string) $request->request->get('name'));
        $clientName = trim((string) $request->request->get('clientName'));
        $responsibleUserId = trim((string) $request->request->get('responsibleUserId'));
        $budgetEuros = filter_var($request->request->get('budgetEuros'), \FILTER_VALIDATE_INT);
        $contractType = ContractType::tryFrom((string) $request->request->get('contractType'));

        if ('' === $name || !$contractType instanceof ContractType) {
            $this->addFlash('error', 'Champs requis : nom et contractualisation.');

            return $this->redirectToRoute('project_new');
        }

        try {
            $project = $this->createProject->create(
                $user,
                $name,
                $clientName,
                '' !== $responsibleUserId ? $responsibleUserId : $user->id(),
                false !== $budgetEuros ? $budgetEuros * 100 : 0,
                $contractType,
                $this->date($request->request->get('startDate')),
                $this->date($request->request->get('endDate')),
            );
            $this->addFlash('success', sprintf('Projet %s créé avec le statut « En préparation ».', $project->code()));

            return $this->redirectToRoute('project_show', ['id' => $project->id()]);
        } catch (ProjectException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $this->redirectToRoute('project_new');
        }
    }

    #[Route('/projets/{id}', name: 'project_show', requirements: ['id' => '[0-9a-f-]{36}'], methods: ['GET'])]
    public function show(#[CurrentUser] User $user, string $id): Response
    {
        $this->authorizer->ensureCan($user, Permission::VIEW_PROJECT);
        $project = $this->projects->find($user->tenantId(), $id);
        if (!$project instanceof Project) {
            throw $this->createNotFoundException('Projet introuvable.');
        }

        return $this->render('project/show.html.twig', [
            'project' => $this->row($project),
            'transitions' => array_map(
                static fn (ProjectStatus $s): array => ['value' => $s->value, 'label' => $s->label()],
                $project->status()->allowedTransitions(),
            ),
            'canEdit' => $this->authorizer->can($user, Permission::EDIT_PROJECT),
        ]);
    }

    #[Route('/projets/{id}/statut', name: 'project_change_status', requirements: ['id' => '[0-9a-f-]{36}'], methods: ['POST'])]
    public function changeStatus(#[CurrentUser] User $user, string $id, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('change_project_status', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('project_show', ['id' => $id]);
        }

        $target = ProjectStatus::tryFrom((string) $request->request->get('status'));
        if (!$target instanceof ProjectStatus) {
            $this->addFlash('error', 'Statut cible invalide.');

            return $this->redirectToRoute('project_show', ['id' => $id]);
        }

        try {
            $this->changeProjectStatus->change($user, $id, $target);
            $this->addFlash('success', sprintf('Statut passé à « %s ».', $target->label()));
        } catch (ProjectException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('project_show', ['id' => $id]);
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Project $project): array
    {
        return [
            'id' => $project->id(),
            'code' => $project->code(),
            'name' => $project->name(),
            'client' => $project->clientName(),
            'status' => $project->status()->label(),
            'statusFamily' => $project->status()->badgeFamily(),
            'budgetEuros' => null !== $project->budgetCents() ? intdiv($project->budgetCents(), 100) : null,
            'contractType' => $project->contractType()?->label(),
            'startDate' => $project->startDate()?->format('d/m/Y'),
            'endDate' => $project->endDate()?->format('d/m/Y'),
        ];
    }

    private function date(mixed $raw): ?DateTimeImmutable
    {
        if (is_string($raw) && '' !== $raw) {
            $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $raw);
            if (false !== $parsed) {
                return $parsed;
            }
        }

        return null;
    }
}
