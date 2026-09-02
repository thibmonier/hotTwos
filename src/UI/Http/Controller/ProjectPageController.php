<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Authorization\Authorizer;
use App\Application\Project\ChangeProjectStatus;
use App\Application\Project\CreateProject;
use App\Domain\Authorization\Permission;
use App\Domain\Project\ContractType;
use App\Domain\Project\Project;
use App\Domain\Project\ExceptionalImputationOpening;
use App\Domain\Project\ExceptionalImputationOpeningRepository;
use App\Domain\Project\ExternalCommitmentRepository;
use App\Domain\Project\ProjectReopening;
use App\Domain\Project\ProjectReopeningRepository;
use App\Domain\Project\ProjectAssignment;
use App\Domain\Project\ProjectAssignmentRepository;
use App\Domain\Project\ProjectException;
use App\Domain\Project\ProjectLot;
use App\Domain\Project\ProjectLotRepository;
use App\Domain\Project\ProjectMilestone;
use App\Domain\Project\ProjectMilestoneRepository;
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
        private readonly ProjectLotRepository $lots,
        private readonly ProjectMilestoneRepository $milestones,
        private readonly ProjectAssignmentRepository $assignments,
        private readonly ExceptionalImputationOpeningRepository $openings,
        private readonly ExternalCommitmentRepository $commitments,
        private readonly ProjectReopeningRepository $reopenings,
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
            'canManage' => $this->authorizer->can($user, Permission::MANAGE_ORGANIZATION),
            'structure' => $this->structureView($user->tenantId(), $project->id(), $project->budgetCents()),
            'milestones' => array_map(
                static fn (ProjectMilestone $m): array => [
                    'name' => $m->name(),
                    'due' => $m->dueDate()->format('d/m/Y'),
                    'status' => $m->status()->label(),
                    'reached' => $m->reachedDate()?->format('d/m/Y'),
                    'billing' => null !== $m->billingTriggerCents() ? intdiv($m->billingTriggerCents(), 100) : null,
                    'id' => $m->id(),
                    'triggered' => $m->billingTriggeredAt() instanceof DateTimeImmutable,
                ],
                $this->milestones->findForProject($user->tenantId(), $project->id()),
            ),
            'assignments' => array_map(
                static fn (ProjectAssignment $a): array => [
                    'id' => $a->id(),
                    'userId' => $a->userId(),
                    'role' => $a->role(),
                    'plannedDays' => $a->plannedDays(),
                    'start' => $a->startDate()?->format('d/m/Y'),
                    'end' => $a->endDate()?->format('d/m/Y'),
                ],
                $this->assignments->findForProject($user->tenantId(), $project->id()),
            ),
            'openings' => array_map(
                static fn (ExceptionalImputationOpening $o): array => [
                    'userId' => $o->userId(),
                    'week' => $o->weekStart()->format('d/m/Y'),
                    'reason' => $o->reason(),
                    'grantedBy' => $o->grantedBy(),
                ],
                $this->openings->findForProject($user->tenantId(), $project->id()),
            ),
            'commitments' => $this->commitmentsView($user->tenantId(), $project->id()),
            'commitmentTypes' => \App\Domain\Project\CommitmentType::cases(),
            'commitmentStatuses' => \App\Domain\Project\CommitmentStatus::cases(),
            'closed' => $project->isClosed(),
            'closedAt' => $project->closedAt()?->format('d/m/Y'),
            'reopenings' => array_map(
                static fn (ProjectReopening $r): array => [
                    'id' => $r->id(),
                    'requestedBy' => $r->requestedBy(),
                    'reason' => $r->reason(),
                    'approved' => $r->isApproved(),
                    'openUntil' => $r->openUntil()?->format('d/m/Y'),
                ],
                $this->reopenings->findForProject($user->tenantId(), $project->id()),
            ),
        ]);
    }

    /**
     * Engagements externes du projet + total des coûts externes (marge partielle — US-033 dégradé).
     *
     * @return array<string, mixed>
     */
    private function commitmentsView(\App\Domain\Tenant\TenantId $tenant, string $projectId): array
    {
        $rows = [];
        $totalCents = 0;
        foreach ($this->commitments->findForProject($tenant, $projectId) as $commitment) {
            $totalCents += $commitment->amountCents();
            $rows[] = [
                'type' => $commitment->type()->label(),
                'label' => $commitment->label(),
                'supplier' => $commitment->supplier(),
                'status' => $commitment->status()->label(),
                'euros' => intdiv($commitment->amountCents(), 100),
            ];
        }

        return ['rows' => $rows, 'totalEuros' => intdiv($totalCents, 100)];
    }

    /**
     * Arbre des lots (racines + sous-lots) et synthèse budget vs budget projet (écart signalé).
     *
     * @return array<string, mixed>
     */
    private function structureView(\App\Domain\Tenant\TenantId $tenant, string $projectId, ?int $projectBudgetCents): array
    {
        $lots = $this->lots->findForProject($tenant, $projectId);
        $rootSumCents = 0;
        $rootSumDays = 0;
        $roots = [];
        foreach ($lots as $lot) {
            if (!$lot->isRoot()) {
                continue;
            }
            $rootSumCents += $lot->budgetCents();
            $rootSumDays += $lot->budgetDays();
            $roots[] = [
                'id' => $lot->id(),
                'name' => $lot->name(),
                'days' => $lot->budgetDays(),
                'euros' => intdiv($lot->budgetCents(), 100),
                'children' => array_map(
                    static fn (ProjectLot $c): array => ['name' => $c->name(), 'days' => $c->budgetDays(), 'euros' => intdiv($c->budgetCents(), 100)],
                    array_values(array_filter($lots, static fn (ProjectLot $c): bool => $c->parentLotId() === $lot->id())),
                ),
            ];
        }

        $budgetEuros = null !== $projectBudgetCents ? intdiv($projectBudgetCents, 100) : null;
        $gapEuros = null !== $projectBudgetCents ? intdiv($rootSumCents - $projectBudgetCents, 100) : null;

        return [
            'roots' => $roots,
            'sumDays' => $rootSumDays,
            'sumEuros' => intdiv($rootSumCents, 100),
            'budgetEuros' => $budgetEuros,
            'gapEuros' => $gapEuros,
        ];
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
