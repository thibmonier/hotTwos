<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Skill\Skill;
use App\Domain\Skill\SkillCategory;
use App\Domain\Skill\SkillRepository;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineSkillRepository implements SkillRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(Skill $skill): void
    {
        $this->entityManager->persist($skill);
        $this->entityManager->flush();
    }

    public function find(TenantId $tenant, string $id): ?Skill
    {
        $skill = $this->entityManager->find(Skill::class, $id);

        return $skill instanceof Skill && $skill->tenantId()->equals($tenant) ? $skill : null;
    }

    public function findForTenant(TenantId $tenant, bool $includeInactive = true): array
    {
        $dql = 'SELECT s FROM '.Skill::class.' s WHERE s.tenantId = :tenant';
        if (!$includeInactive) {
            $dql .= ' AND s.active = true';
        }
        $dql .= ' ORDER BY s.category ASC, s.label ASC';

        /** @var list<Skill> $skills */
        $skills = $this->entityManager->createQuery($dql)
            ->setParameter('tenant', $tenant->toString())
            ->getResult();

        return $skills;
    }

    public function existsByLabel(TenantId $tenant, SkillCategory $category, string $normalizedLabel): bool
    {
        $count = (int) $this->entityManager->createQuery(
            'SELECT COUNT(s.id) FROM '.Skill::class.' s WHERE s.tenantId = :tenant AND s.category = :category AND s.normalizedLabel = :label',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('category', $category->value)
            ->setParameter('label', $normalizedLabel)
            ->getSingleScalarResult();

        return $count > 0;
    }
}
