<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Authorization\Authorizer;
use App\Application\Skill\ManageSkillCatalog;
use App\Domain\Audit\AuditAction;
use App\Domain\Audit\ConfigAuditRecorder;
use App\Domain\Authorization\Permission;
use App\Domain\Skill\Skill;
use App\Domain\Skill\SkillCategory;
use App\Domain\Skill\SkillException;
use App\Domain\Skill\SkillLevelScale;
use App\Domain\Skill\SkillLevelScaleRepository;
use App\Domain\Skill\SkillRepository;
use App\Domain\User\UserRepository;
use App\Domain\User\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * US-013 (EF-REF-10/11) — paramétrage du référentiel de compétences (catégories, échelle, association).
 * Réservé à `MANAGE_ORGANIZATION`.
 */
final class SkillController extends AbstractController
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly ManageSkillCatalog $catalog,
        private readonly SkillRepository $skills,
        private readonly SkillLevelScaleRepository $scales,
        private readonly UserRepository $users,
        private readonly ConfigAuditRecorder $audit,
    ) {
    }

    #[Route('/parametrage/competences', name: 'skill_index', methods: ['GET'])]
    public function index(#[CurrentUser] User $user): Response
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_ORGANIZATION);
        $tenant = $user->tenantId();

        $scale = $this->scales->findForTenant($tenant) ?? new SkillLevelScale($tenant);
        $userIds = $this->users->findIdsByTenant($tenant);

        return $this->render('parametrage/skills.html.twig', [
            'skills' => array_map(
                static fn (Skill $s): array => [
                    'id' => $s->id(),
                    'category' => $s->category()->label(),
                    'label' => $s->label(),
                    'active' => $s->isActive(),
                ],
                $this->skills->findForTenant($tenant),
            ),
            'levels' => $scale->levels(),
            'categories' => SkillCategory::cases(),
            'collaborators' => $this->users->findDisplayNamesByIds($tenant, $userIds),
        ]);
    }

    #[Route('/parametrage/competences/skill', name: 'skill_add', methods: ['POST'])]
    public function add(#[CurrentUser] User $user, Request $request): RedirectResponse
    {
        return $this->guarded($user, $request, 'skill_add', function () use ($user, $request): void {
            $category = SkillCategory::tryFrom((string) $request->request->get('category'));
            if (null === $category) {
                throw new SkillException('Catégorie invalide.');
            }
            $skill = $this->catalog->createSkill($user, $category, (string) $request->request->get('label'));
            $this->audit->record($user->tenantId(), $user->id(), AuditAction::CREATION, 'Compétence', $skill->label());
            $this->addFlash('success', 'Compétence ajoutée.');
        });
    }

    #[Route('/parametrage/competences/skill/{id}/desactivation', name: 'skill_deactivate', requirements: ['id' => '[0-9a-f-]{36}'], methods: ['POST'])]
    public function deactivate(#[CurrentUser] User $user, string $id, Request $request): RedirectResponse
    {
        return $this->guarded($user, $request, 'skill_deactivate', function () use ($user, $id): void {
            $skill = $this->skills->find($user->tenantId(), $id);
            $this->catalog->deactivateSkill($user, $id);
            if ($skill instanceof Skill) {
                $this->audit->record($user->tenantId(), $user->id(), AuditAction::DESACTIVATION, 'Compétence', $skill->label());
            }
            $this->addFlash('success', 'Compétence désactivée.');
        });
    }

    #[Route('/parametrage/competences/echelle', name: 'skill_scale', methods: ['POST'])]
    public function scale(#[CurrentUser] User $user, Request $request): RedirectResponse
    {
        return $this->guarded($user, $request, 'skill_scale', function () use ($user, $request): void {
            $levels = array_values(array_filter(array_map(trim(...), explode(',', (string) $request->request->get('levels')))));
            $this->catalog->configureScale($user, $levels);
            $this->addFlash('success', 'Échelle de niveaux enregistrée.');
        });
    }

    #[Route('/parametrage/competences/association', name: 'skill_assign', methods: ['POST'])]
    public function assign(#[CurrentUser] User $user, Request $request): RedirectResponse
    {
        return $this->guarded($user, $request, 'skill_assign', function () use ($user, $request): void {
            $level = filter_var($request->request->get('level'), \FILTER_VALIDATE_INT);
            if (false === $level) {
                throw new SkillException('Niveau invalide.');
            }
            $this->catalog->assignSkill($user, (string) $request->request->get('collaborator'), (string) $request->request->get('skill'), $level);
            $this->addFlash('success', 'Compétence associée.');
        });
    }

    private function guarded(User $user, Request $request, string $token, callable $action): RedirectResponse
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_ORGANIZATION);
        if (!$this->isCsrfTokenValid($token, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('skill_index');
        }

        try {
            $action();
        } catch (SkillException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('skill_index');
    }
}
