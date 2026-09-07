<?php

declare(strict_types=1);

namespace App\Application\Skill;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Skill\Skill;
use App\Domain\Skill\SkillAssignment;
use App\Domain\Skill\SkillAssignmentRepository;
use App\Domain\Skill\SkillCategory;
use App\Domain\Skill\SkillException;
use App\Domain\Skill\SkillLevelScale;
use App\Domain\Skill\SkillLevelScaleRepository;
use App\Domain\Skill\SkillRepository;
use App\Domain\User\User;

/**
 * US-013 (EF-REF-10/11) — gestion du référentiel de compétences : création (unicité insensible à la
 * casse), désactivation (RG-REF-1), configuration de l'échelle, association collaborateur↔compétence.
 * Réservé à `MANAGE_ORGANIZATION`.
 */
final readonly class ManageSkillCatalog
{
    public function __construct(
        private Authorizer $authorizer,
        private SkillRepository $skills,
        private SkillLevelScaleRepository $scales,
        private SkillAssignmentRepository $assignments,
    ) {
    }

    public function createSkill(User $user, SkillCategory $category, string $label): Skill
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_ORGANIZATION);
        $tenant = $user->tenantId();

        if ($this->skills->existsByLabel($tenant, $category, Skill::normalize($label))) {
            throw new SkillException(sprintf('La compétence « %s » existe déjà dans la catégorie %s.', trim($label), $category->label()));
        }

        $skill = new Skill($tenant, $category, $label);
        $this->skills->save($skill);

        return $skill;
    }

    public function deactivateSkill(User $user, string $skillId): void
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_ORGANIZATION);
        $skill = $this->skills->find($user->tenantId(), $skillId);
        if (!$skill instanceof Skill) {
            throw new SkillException('Compétence introuvable.');
        }
        $skill->deactivate();
        $this->skills->save($skill);
    }

    /**
     * @param list<string> $levels
     */
    public function configureScale(User $user, array $levels): SkillLevelScale
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_ORGANIZATION);
        $tenant = $user->tenantId();

        $scale = $this->scales->findForTenant($tenant);
        if ($scale instanceof SkillLevelScale) {
            $scale->reconfigure($levels);
        } else {
            $scale = new SkillLevelScale($tenant, $levels);
        }
        $this->scales->save($scale);

        return $scale;
    }

    public function assignSkill(User $user, string $collaboratorId, string $skillId, int $level): SkillAssignment
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_ORGANIZATION);
        $tenant = $user->tenantId();

        $skill = $this->skills->find($tenant, $skillId);
        if (!$skill instanceof Skill || !$skill->isActive()) {
            throw new SkillException('Compétence introuvable ou désactivée.');
        }

        $scale = $this->scales->findForTenant($tenant) ?? new SkillLevelScale($tenant);
        if (!$scale->isValidLevel($level)) {
            throw new SkillException(sprintf('Niveau invalide : %d (échelle de 1 à %d).', $level, $scale->count()));
        }

        $existing = $this->assignments->find($tenant, $collaboratorId, $skillId);
        if ($existing instanceof SkillAssignment) {
            $existing->reassign($level);
            $this->assignments->save($existing);

            return $existing;
        }

        $assignment = new SkillAssignment($tenant, $collaboratorId, $skillId, $level);
        $this->assignments->save($assignment);

        return $assignment;
    }
}
