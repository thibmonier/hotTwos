<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Skill\SkillLevelScale;
use App\Domain\Skill\SkillLevelScaleRepository;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineSkillLevelScaleRepository implements SkillLevelScaleRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findForTenant(TenantId $tenant): ?SkillLevelScale
    {
        $scale = $this->entityManager->createQuery(
            'SELECT s FROM '.SkillLevelScale::class.' s WHERE s.tenantId = :tenant',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $scale instanceof SkillLevelScale ? $scale : null;
    }

    public function save(SkillLevelScale $scale): void
    {
        $this->entityManager->persist($scale);
        $this->entityManager->flush();
    }
}
