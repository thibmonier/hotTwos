<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Skill\SkillAssignment;
use App\Domain\Skill\SkillAssignmentRepository;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineSkillAssignmentRepository implements SkillAssignmentRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(SkillAssignment $assignment): void
    {
        $this->entityManager->persist($assignment);
        $this->entityManager->flush();
    }

    public function find(TenantId $tenant, string $userId, string $skillId): ?SkillAssignment
    {
        $assignment = $this->entityManager->createQuery(
            'SELECT a FROM '.SkillAssignment::class.' a WHERE a.tenantId = :tenant AND a.userId = :user AND a.skillId = :skill',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('user', $userId)
            ->setParameter('skill', $skillId)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $assignment instanceof SkillAssignment ? $assignment : null;
    }

    public function countBySkill(TenantId $tenant): array
    {
        /** @var list<array{skillId: string, c: int}> $rows */
        $rows = $this->entityManager->createQuery(
            'SELECT a.skillId AS skillId, COUNT(a.id) AS c FROM '.SkillAssignment::class.' a WHERE a.tenantId = :tenant GROUP BY a.skillId',
        )
            ->setParameter('tenant', $tenant->toString())
            ->getResult();

        $map = [];
        foreach ($rows as $row) {
            $map[$row['skillId']] = (int) $row['c'];
        }

        return $map;
    }

    public function levelsBySkillAtLeast(TenantId $tenant, string $skillId, int $minLevel): array
    {
        /** @var list<array{userId: string, level: int}> $rows */
        $rows = $this->entityManager->createQuery(
            'SELECT a.userId AS userId, a.level AS level FROM '.SkillAssignment::class.' a'
            .' WHERE a.tenantId = :tenant AND a.skillId = :skill AND a.level >= :min',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setParameter('skill', $skillId)
            ->setParameter('min', $minLevel)
            ->getResult();

        $map = [];
        foreach ($rows as $row) {
            $map[$row['userId']] = (int) $row['level'];
        }

        return $map;
    }
}
