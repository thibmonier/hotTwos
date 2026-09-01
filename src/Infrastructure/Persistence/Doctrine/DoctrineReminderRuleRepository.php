<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Reminder\ReminderRule;
use App\Domain\Reminder\ReminderRuleRepository;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Implémentation Doctrine du {@see ReminderRuleRepository} (US-056, DIP). Tenant explicite.
 */
final readonly class DoctrineReminderRuleRepository implements ReminderRuleRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(ReminderRule $rule): void
    {
        $this->entityManager->persist($rule);
        $this->entityManager->flush();
    }

    public function findForTenant(TenantId $tenant): ?ReminderRule
    {
        /** @var ReminderRule|null $rule */
        $rule = $this->entityManager->createQuery(
            'SELECT r FROM '.ReminderRule::class.' r WHERE r.tenantId = :tenant',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $rule;
    }
}
