<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Authorization\Authorizer;
use App\Application\Budget\ViewProjectBudgetTracking;
use App\Application\Project\AddBudgetAmendment;
use App\Application\Project\ChangeProjectStatus;
use App\Application\Project\CreateProject;
use App\Application\Project\DefineLotProfileBudget;
use App\Domain\Authorization\Permission;
use App\Application\Invoice\IssueInvoice;
use App\Domain\Client\Client;
use App\Domain\Pricing\Profile;
use App\Domain\Pricing\ProfileRepository;
use App\Domain\Project\BudgetAmendment;
use App\Domain\Project\BudgetAmendmentRepository;
use App\Domain\Project\CurrentProjectBudget;
use App\Domain\Project\LotProfileBudget;
use App\Domain\Project\LotProfileBudgetRepository;
use App\Domain\Project\ProfileBudgetCalculator;
use App\Domain\Tenant\TenantId;
use Psr\Clock\ClockInterface;
use App\Domain\Client\ClientRepository;
use App\Domain\Invoice\Invoice;
use App\Domain\Invoice\InvoiceException;
use App\Domain\Invoice\InvoiceRepository;
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
use RuntimeException;

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
        private readonly ViewProjectBudgetTracking $budgetTracking,
        private readonly ClientRepository $clients,
        private readonly InvoiceRepository $invoices,
        private readonly IssueInvoice $issueInvoice,
        private readonly BudgetAmendmentRepository $amendments,
        private readonly CurrentProjectBudget $currentBudget,
        private readonly AddBudgetAmendment $addBudgetAmendment,
        private readonly LotProfileBudgetRepository $lotProfileBudgets,
        private readonly ProfileBudgetCalculator $profileBudgetCalculator,
        private readonly ProfileRepository $profiles,
        private readonly DefineLotProfileBudget $defineLotProfileBudget,
        private readonly ClockInterface $clock,
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

        // Suivi budgétaire (US-072) : réservé aux rôles finance/direction (HAB-1) ; le read service
        // applique lui-même le gating fin (coût/marge/dérive conditionnés à VIEW_COLLABORATOR_COST).
        $canViewFinancials = $this->authorizer->can($user, Permission::VIEW_PROJECT_FINANCIALS);

        // US-033 : budget courant (initial + Σ avenants) + historique des avenants.
        $amendments = $this->amendments->findForProject($user->tenantId(), $project->id());
        $current = $this->currentBudget->current($project->budgetCents(), $project->revenueBudgetCents(), $amendments);

        return $this->render('project/show.html.twig', [
            'project' => $this->row($project),
            'canViewFinancials' => $canViewFinancials,
            'budgetTracking' => $canViewFinancials ? $this->budgetTracking->forProject($user, $project->id()) : null,
            'clientId' => $project->clientId(),
            'clients' => array_map(
                static fn (Client $c): array => ['id' => $c->id(), 'name' => $c->name()],
                $this->clients->findAllByTenant($user->tenantId()),
            ),
            'invoices' => $canViewFinancials ? array_map(
                static fn (Invoice $i): array => [
                    'period' => $i->period(),
                    'amountCents' => $i->amountCents(),
                    'issuedAt' => $i->issuedAt()->format('d/m/Y'),
                ],
                $this->invoices->findForProject($user->tenantId(), $project->id()),
            ) : [],
            'transitions' => array_map(
                static fn (ProjectStatus $s): array => ['value' => $s->value, 'label' => $s->label()],
                $project->status()->allowedTransitions(),
            ),
            'canEdit' => $this->authorizer->can($user, Permission::EDIT_PROJECT),
            'canManage' => $this->authorizer->can($user, Permission::MANAGE_ORGANIZATION),
            'structure' => $this->structureView($user->tenantId(), $project->id(), $current->costCents),
            'initialBudgetEuros' => null !== $project->budgetCents() ? intdiv($project->budgetCents(), 100) : null,
            'currentBudgetEuros' => null !== $current->costCents ? intdiv($current->costCents, 100) : null,
            'budgetAmendments' => $canViewFinancials ? array_map(
                static fn (BudgetAmendment $a): array => [
                    'deltaCostEuros' => intdiv($a->deltaCostCents(), 100),
                    'deltaRevenueEuros' => intdiv($a->deltaRevenueCents(), 100),
                    'reason' => $a->reason(),
                    'at' => $a->recordedAt()->format('d/m/Y'),
                ],
                $amendments,
            ) : [],
            // US-078 — budget de charge par profil (équivalents € aux taux à la date de référence).
            'profiles' => array_values(array_map(
                static fn (Profile $p): array => ['id' => $p->id(), 'name' => $p->name()],
                array_filter($this->profiles->findByTenant($user->tenantId()), static fn (Profile $p): bool => $p->isActive()),
            )),
            'lotProfileBudgets' => $this->profileBudgetView($user->tenantId(), $project->id(), $project->startDate() ?? $this->clock->now()),
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
    private function commitmentsView(TenantId $tenant, string $projectId): array
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
    private function structureView(TenantId $tenant, string $projectId, ?int $projectBudgetCents): array
    {
        $lots = $this->lots->findForProject($tenant, $projectId);

        // Pré-groupe les sous-lots par lot parent en une passe (évite un O(n²) de présentation).
        $childrenByParent = [];
        foreach ($lots as $lot) {
            if (!$lot->isRoot()) {
                $childrenByParent[(string) $lot->parentLotId()][] = ['id' => $lot->id(), 'name' => $lot->name(), 'days' => $lot->budgetDays(), 'euros' => intdiv($lot->budgetCents(), 100), 'progress' => $lot->physicalProgressPercent(), 'raf' => $lot->remainingWorkDays()];
            }
        }

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
                'progress' => $lot->physicalProgressPercent(),
                'raf' => $lot->remainingWorkDays(),
                'children' => $childrenByParent[$lot->id()] ?? [],
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

    #[Route('/projets/{id}/client', name: 'project_attach_client', requirements: ['id' => '[0-9a-f-]{36}'], methods: ['POST'])]
    public function attachClient(#[CurrentUser] User $user, string $id, Request $request): RedirectResponse
    {
        $this->authorizer->ensureCan($user, Permission::EDIT_PROJECT);
        if (!$this->isCsrfTokenValid('attach_client', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('project_show', ['id' => $id]);
        }

        $project = $this->projects->find($user->tenantId(), $id);
        if (!$project instanceof Project) {
            throw $this->createNotFoundException('Projet introuvable.');
        }

        $clientId = trim((string) $request->request->get('clientId'));
        // Valide l'appartenance du client au tenant avant rattachement (deny-by-default).
        if ('' !== $clientId && !$this->clients->find($user->tenantId(), $clientId) instanceof Client) {
            $this->addFlash('error', 'Client inconnu.');

            return $this->redirectToRoute('project_show', ['id' => $id]);
        }

        $project->attachClient('' !== $clientId ? $clientId : null);
        $this->projects->save($project);
        $this->addFlash('success', 'Client du projet mis à jour.');

        return $this->redirectToRoute('project_show', ['id' => $id]);
    }

    #[Route('/projets/{id}/facture', name: 'project_issue_invoice', requirements: ['id' => '[0-9a-f-]{36}'], methods: ['POST'])]
    public function issueInvoice(#[CurrentUser] User $user, string $id, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('issue_invoice', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('project_show', ['id' => $id]);
        }

        $period = trim((string) $request->request->get('period'));
        $amountEuros = filter_var($request->request->get('amountEuros'), \FILTER_VALIDATE_INT);

        try {
            $this->issueInvoice->issue($user, $id, $period, false !== $amountEuros ? $amountEuros * 100 : 0);
            $this->addFlash('success', sprintf('Facture émise pour la période %s.', $period));
        } catch (InvoiceException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('project_show', ['id' => $id]);
    }

    /**
     * Budget de charge par profil, par lot (US-078) : lignes {profil, jours} + équivalents € vente/coût
     * aux taux de la date de référence. Indexé par id de lot.
     *
     * @return array<string, array<string, mixed>>
     */
    private function profileBudgetView(TenantId $tenant, string $projectId, DateTimeImmutable $referenceDate): array
    {
        $profileNames = [];
        foreach ($this->profiles->findByTenant($tenant) as $profile) {
            $profileNames[$profile->id()] = $profile->name();
        }

        $linesByLot = [];
        foreach ($this->lotProfileBudgets->findForProject($tenant, $projectId) as $line) {
            $linesByLot[$line->lotId()][] = $line;
        }

        $view = [];
        foreach ($linesByLot as $lotId => $lines) {
            $breakdown = $this->profileBudgetCalculator->compute($tenant, $lines, $referenceDate);
            $view[$lotId] = [
                'rows' => array_map(
                    static fn (LotProfileBudget $l): array => ['profile' => $profileNames[$l->profileId()] ?? $l->profileId(), 'days' => $l->days()],
                    $lines,
                ),
                'days' => $breakdown->days,
                'sellingEuros' => intdiv($breakdown->sellingCents, 100),
                'costEuros' => intdiv($breakdown->costCents, 100),
                'missing' => $breakdown->hasMissingRate(),
            ];
        }

        return $view;
    }

    #[Route('/projets/{id}/lots/{lotId}/budget-profil', name: 'project_lot_profile_budget', requirements: ['id' => '[0-9a-f-]{36}', 'lotId' => '[0-9a-f-]{36}'], methods: ['POST'])]
    public function defineLotProfileBudget(#[CurrentUser] User $user, string $id, string $lotId, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('project_structure', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('project_show', ['id' => $id]);
        }

        $profileId = trim((string) $request->request->get('profileId'));
        $days = filter_var($request->request->get('days'), \FILTER_VALIDATE_INT);

        if ('' === $profileId || false === $days) {
            $this->addFlash('error', 'Budget par profil : profil et jours requis.');

            return $this->redirectToRoute('project_show', ['id' => $id, '_fragment' => 'panel-structure']);
        }

        try {
            $this->defineLotProfileBudget->define($user, $lotId, $profileId, $days);
            $this->addFlash('success', 'Budget de charge par profil enregistré.');
        } catch (ProjectException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('project_show', ['id' => $id, '_fragment' => 'panel-structure']);
    }

    #[Route('/projets/{id}/pilotage/export', name: 'project_pilotage_export', requirements: ['id' => '[0-9a-f-]{36}'], methods: ['GET'])]
    public function exportPilotage(#[CurrentUser] User $user, string $id): Response
    {
        $this->authorizer->ensureCan($user, Permission::VIEW_PROJECT_FINANCIALS);
        $project = $this->projects->find($user->tenantId(), $id);
        if (!$project instanceof Project) {
            throw $this->createNotFoundException('Projet introuvable.');
        }

        $bt = $this->budgetTracking->forProject($user, $id);
        $lots = $this->lots->findForProject($user->tenantId(), $id);

        // Colonnes coût réservées à VIEW_COLLABORATOR_COST (HAB-1) : $bt gate déjà ces champs à null.
        $euro = static fn (?int $cents): string => null === $cents ? '' : number_format($cents / 100, 2, '.', '');
        $nullable = static fn (?int $value): string => null === $value ? '' : (string) $value;
        $ecart = (null !== $bt->landingCostCents && null !== $bt->costBudgetCents) ? $bt->landingCostCents - $bt->costBudgetCents : null;

        $rootDays = 0;
        foreach ($lots as $lot) {
            if ($lot->isRoot()) {
                $rootDays += $lot->budgetDays();
            }
        }

        $rows = [['Section', 'Élément', 'Budget (j)', 'Avancement (%)', 'RAF (j)', 'Budget courant (€)', 'Consommé (€)', 'Atterrissage (€)', 'Écart (€)']];
        $rows[] = ['Projet', $project->name(), (string) $rootDays, $nullable($bt->landingProgressPercent), '', $euro($bt->costBudgetCents), $euro($bt->realizedCostCents), $euro($bt->landingCostCents), $euro($ecart)];
        foreach ($lots as $lot) {
            $rows[] = ['Lot', $lot->name(), (string) $lot->budgetDays(), $nullable($lot->physicalProgressPercent()), $nullable($lot->remainingWorkDays()), '', '', '', ''];
        }

        $handle = fopen('php://temp', 'r+');
        if (false === $handle) {
            throw new RuntimeException('Impossible de générer le fichier CSV.');
        }
        foreach ($rows as $row) {
            fputcsv($handle, $row, ';', '"', '');
        }
        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return new Response($csv, Response::HTTP_OK, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => sprintf('attachment; filename="pilotage-%s.csv"', $project->code()),
        ]);
    }

    #[Route('/projets/{id}/interne', name: 'project_toggle_internal', requirements: ['id' => '[0-9a-f-]{36}'], methods: ['POST'])]
    public function toggleInternal(#[CurrentUser] User $user, string $id, Request $request): RedirectResponse
    {
        $this->authorizer->ensureCan($user, Permission::EDIT_PROJECT);
        if (!$this->isCsrfTokenValid('toggle_internal', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('project_show', ['id' => $id]);
        }

        $project = $this->projects->find($user->tenantId(), $id);
        if (!$project instanceof Project) {
            throw $this->createNotFoundException('Projet introuvable.');
        }

        $project->markInternal($request->request->has('internal'));
        $this->projects->save($project);
        $this->addFlash('success', $project->isInternal() ? 'Projet marqué « interne non facturable ».' : 'Projet marqué facturable.');

        return $this->redirectToRoute('project_show', ['id' => $id]);
    }

    #[Route('/projets/{id}/avenant', name: 'project_budget_amend', requirements: ['id' => '[0-9a-f-]{36}'], methods: ['POST'])]
    public function amendBudget(#[CurrentUser] User $user, string $id, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('budget_amend', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('project_show', ['id' => $id]);
        }

        $deltaCost = filter_var($request->request->get('deltaCostEuros'), \FILTER_VALIDATE_INT);
        $deltaRevenue = filter_var($request->request->get('deltaRevenueEuros'), \FILTER_VALIDATE_INT);
        $reason = trim((string) $request->request->get('reason'));

        try {
            $this->addBudgetAmendment->add(
                $user,
                $id,
                false !== $deltaCost ? $deltaCost * 100 : 0,
                false !== $deltaRevenue ? $deltaRevenue * 100 : 0,
                $reason,
            );
            $this->addFlash('success', 'Avenant budgétaire enregistré.');
        } catch (ProjectException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('project_show', ['id' => $id, '_fragment' => 'panel-budget']);
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
            'internal' => $project->isInternal(),
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
