<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Audit\AuditAction;
use App\Domain\Audit\ConfigAuditEntry;
use App\Domain\Audit\ConfigAuditRecorder;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

final readonly class DoctrineConfigAuditRecorder implements ConfigAuditRecorder
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ClockInterface $clock,
    ) {
    }

    public function record(
        TenantId $tenant,
        string $actorUserId,
        AuditAction $action,
        string $objectType,
        string $objectLabel,
        ?string $field = null,
        ?string $valueBefore = null,
        ?string $valueAfter = null,
    ): void {
        $this->entityManager->persist(new ConfigAuditEntry(
            $tenant,
            $actorUserId,
            $action,
            $objectType,
            $objectLabel,
            $field,
            $valueBefore,
            $valueAfter,
            $this->clock->now(),
        ));
        $this->entityManager->flush();
    }

    public function findForTenant(TenantId $tenant, ?string $objectType = null, ?string $actorUserId = null): array
    {
        $dql = 'SELECT e FROM '.ConfigAuditEntry::class.' e WHERE e.tenantId = :tenant';
        $params = ['tenant' => $tenant->toString()];
        if (null !== $objectType && '' !== $objectType) {
            $dql .= ' AND e.objectType = :objectType';
            $params['objectType'] = $objectType;
        }
        if (null !== $actorUserId && '' !== $actorUserId) {
            $dql .= ' AND e.actorUserId = :actor';
            $params['actor'] = $actorUserId;
        }
        $dql .= ' ORDER BY e.recordedAt DESC';

        $query = $this->entityManager->createQuery($dql);
        foreach ($params as $key => $value) {
            $query->setParameter($key, $value);
        }

        /** @var list<ConfigAuditEntry> $entries */
        $entries = $query->getResult();

        return $entries;
    }
}
