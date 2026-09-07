<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Authorization\Authorizer;
use App\Application\Staffing\SearchStaffing;
use App\Domain\Authorization\Permission;
use App\Domain\Skill\Skill;
use App\Domain\Skill\SkillLevelScale;
use App\Domain\Skill\SkillLevelScaleRepository;
use App\Domain\Skill\SkillRepository;
use App\Domain\User\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * US-040 (EPIC-004) — recherche de staffing par compétence & disponibilité. Gating
 * VIEW_TEAM_COMPLETENESS ; aucun coût affiché (HAB-1). Seules les compétences actives sont proposées.
 */
final class StaffingSearchController extends AbstractController
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly SearchStaffing $search,
        private readonly SkillRepository $skills,
        private readonly SkillLevelScaleRepository $scales,
        private readonly ClockInterface $clock,
    ) {
    }

    #[Route('/planification/recherche', name: 'staffing_search', methods: ['GET'])]
    public function index(#[CurrentUser] User $user, Request $request): Response
    {
        $this->authorizer->ensureCan($user, Permission::VIEW_TEAM_COMPLETENESS);
        $tenant = $user->tenantId();

        $activeSkills = array_values(array_filter(
            $this->skills->findForTenant($tenant, false),
            static fn (Skill $s): bool => $s->isActive(),
        ));
        $scale = $this->scales->findForTenant($tenant) ?? new SkillLevelScale($tenant);

        $skillId = (string) $request->query->get('skill', '');
        $minLevel = max(1, (int) $request->query->get('level', 1));
        $period = (string) $request->query->get('period', $this->clock->now()->format('Y-m'));
        if (1 !== preg_match('/^\d{4}-\d{2}$/', $period)) {
            $period = $this->clock->now()->format('Y-m');
        }

        $candidates = '' !== $skillId ? $this->search->search($user, $skillId, $minLevel, $period) : [];

        return $this->render('planification/staffing-search.html.twig', [
            'skills' => array_map(static fn (Skill $s): array => ['id' => $s->id(), 'label' => $s->label()], $activeSkills),
            'levels' => $scale->levels(),
            'selectedSkill' => $skillId,
            'minLevel' => $minLevel,
            'period' => $period,
            'searched' => '' !== $skillId,
            'candidates' => $candidates,
        ]);
    }
}
